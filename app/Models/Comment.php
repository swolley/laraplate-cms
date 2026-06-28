<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\CMS\Database\Factories\CommentFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Scopes\CommentTranslationScope;
use Modules\CMS\Services\CommentApprovalCapture;
use Modules\CMS\Services\ContentRatingService;
use Modules\Core\Events\ModificationApproved;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\Concerns\HasApprovals;
use Modules\Core\Models\Concerns\HasTranslations;
use Modules\Core\Models\User;
use Modules\Core\Overrides\Model;
use Override;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @property int|null $content_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string|null $body
 *
 * @mixin \Eloquent
 * @mixin IdeHelperComment
 */
final class Comment extends Model
{
    use HasApprovals, HasRecursiveRelationships, HasTranslations {
        HasApprovals::toArray as private approvalsToArray;
        HasTranslations::toArray as private translationsToArray;
    }

    public ?int $pending_rating_score = null;

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::Comments->value;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'content_id',
        'user_id',
    ];

    /**
     * @var list<string>
     */
    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public static function captureSave(self $item): bool
    {
        return CommentApprovalCapture::capture($item);
    }

    /**
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<ContentRating, $this>
     */
    public function rating(): HasOne
    {
        return $this->hasOne(ContentRating::class, 'comment_id');
    }

    /**
     * @return HasOne<CommentTranslation, $this>
     */
    public function translation(): HasOne
    {
        $current_locale = LocaleContext::get();
        $fallback_enabled = $this->translationFallbackEnabledBySettings();

        $relation = $this->hasOne(CommentTranslation::class);

        if ($fallback_enabled) {
            $relation
                ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$current_locale])
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $relation->where(
                (new CommentTranslation())->qualifyColumn('locale'),
                $current_locale,
            );
        }

        return $relation;
    }

    public function getTranslation(?string $locale = null, ?bool $with_fallback = null): ?CommentTranslation
    {
        $locale ??= LocaleContext::get();

        $translation = $this->translations()
            ->where((new CommentTranslation())->qualifyColumn('locale'), $locale)
            ->first();

        if ($translation instanceof CommentTranslation) {
            return $translation;
        }

        if ($with_fallback === false) {
            return null;
        }

        $fallback = $this->translations()
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        return $fallback instanceof CommentTranslation ? $fallback : null;
    }

    public function getOriginalTranslation(): ?CommentTranslation
    {
        $translation = $this->translations()
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        return $translation instanceof CommentTranslation ? $translation : null;
    }

    /**
     * @param  array<string, mixed>|null  $parsed
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(?array $parsed = null): array
    {
        if ($this->getPreviewAttribute()) {
            return $this->approvalsToArray($parsed);
        }

        return $this->translationsToArray($parsed);
    }

    public function applyModificationChanges(\Modules\Core\Models\Modification $modification, bool $approved): void
    {
        if (! $approved || ! $this->updateWhenApproved) {
            if ($approved === false) {
                $modification->active = false;
                $modification->save();
            }

            return;
        }

        $this->setForcedApprovalUpdate(true);
        /** @var array<string, array{original: mixed, modified: mixed}> $changes */
        $changes = $modification->modifications ?? [];

        foreach ($changes as $key => $change) {
            if ($key === 'locale') {
                continue;
            }

            if (in_array($key, ['body', 'rating_score'], true)) {
                if ($key === 'body') {
                    $locale_value = $changes['locale']['modified'] ?? LocaleContext::get();
                    $locale = is_string($locale_value) ? $locale_value : LocaleContext::get();
                    $modified_body = $change['modified'] ?? null;
                    $this->inLocale($locale)->body = is_string($modified_body) ? $modified_body : null;
                }

                continue;
            }

            $this->{$key} = $change['modified'];
        }

        $this->save();

        $modified_rating = $changes['rating_score']['modified'] ?? null;
        $rating_score = is_int($modified_rating)
            ? $modified_rating
            : (is_numeric($modified_rating) ? (int) $modified_rating : null);

        resolve(ContentRatingService::class)->syncFromApprovedComment($this, $rating_score);

        event(new ModificationApproved($modification, $this));

        $modification->active = false;
        $modification->save();
    }

    public function setRatingScoreAttribute(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->pending_rating_score = null;

            return;
        }

        if (is_int($value)) {
            $this->pending_rating_score = $value;

            return;
        }

        if (is_numeric($value)) {
            $this->pending_rating_score = (int) $value;

            return;
        }

        $this->pending_rating_score = null;
    }

    public function hasPendingBodyForCurrentLocale(): bool
    {
        $locale = LocaleContext::get();

        return isset($this->pending_translations[$locale]['body'])
            && $this->pending_translations[$locale]['body'] !== '';
    }

    protected static function bootHasTranslations(): void
    {
        self::addGlobalScope(new CommentTranslationScope());

        self::saved(function (self $comment): void {
            $comment->savePendingTranslations();
        });
    }

    protected static function booted(): void
    {
        self::bootRequiresApproval();
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    protected static function bootRequiresApproval(): void
    {
        self::saving(function (self $comment): ?bool {
            if ($comment->isForcedApprovalUpdate()) {
                $comment->setForcedApprovalUpdate(false);

                return null;
            }

            $dirty = $comment->getDirtyForApproval();

            if ($comment->requiresApprovalWhen($dirty)) {
                return self::captureSave($comment);
            }

            return null;
        });
    }

    protected function getTranslatableFieldValue(string $key): mixed
    {
        $locale = LocaleContext::get();

        if (isset($this->pending_translations[$locale][$key])) {
            return $this->pending_translations[$locale][$key];
        }

        return $this->getTranslation($locale)?->{$key};
    }

    /**
     * @param  array<string, mixed>  $modifications
     */
    protected function requiresApprovalWhen(array $modifications): bool
    {
        if ($this->hasPendingBodyForCurrentLocale()) {
            $locale = LocaleContext::get();
            $modifications['body'] = $this->pending_translations[$locale]['body'];
        }

        if (! array_key_exists('body', $modifications)) {
            return false;
        }

        $user = $this->modifier();

        return ! ($user && ($user->isAdmin() || $user->isSuperAdmin() && $user->can('approve.' . $this->getTable())));
    }

    protected function modifier(): ?User
    {
        return auth()->user();
    }

    protected function casts(): array
    {
        return [
            'content_id' => 'integer',
            'user_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDirtyForApproval(): array
    {
        $dirty = $this->getDirty();

        if ($this->hasPendingBodyForCurrentLocale()) {
            $locale = LocaleContext::get();
            $dirty['body'] = $this->pending_translations[$locale]['body'];
        }

        if ($this->pending_rating_score !== null) {
            $dirty['rating_score'] = $this->pending_rating_score;
        }

        return $dirty;
    }
}
