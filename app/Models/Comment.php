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
 * @property int $content_id
 * @property int $user_id
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
        return $this->belongsTo(user_class());
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

        $relation = $this->hasOne(self::getTranslationModelClass());

        if ($fallback_enabled) {
            $relation
                ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$current_locale])
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $relation->where('locale', $current_locale);
        }

        return $relation;
    }

    public function getTranslation(?string $locale = null, ?bool $with_fallback = null): ?CommentTranslation
    {
        $locale ??= LocaleContext::get();

        $translation = $this->translations()->where('locale', $locale)->first();

        if ($translation !== null) {
            return $translation;
        }

        if ($with_fallback === false) {
            return null;
        }

        return $this->getOriginalTranslation();
    }

    public function getOriginalTranslation(): ?CommentTranslation
    {
        return $this->translations()
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

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
        $changes = $modification->modifications;

        foreach ($changes as $key => $change) {
            if ($key === 'locale') {
                continue;
            }

            if (in_array($key, ['body', 'rating_score'], true)) {
                if ($key === 'body') {
                    $locale = (string) ($changes['locale']['modified'] ?? LocaleContext::get());
                    $this->inLocale($locale)->body = $change['modified'];
                }

                continue;
            }

            $this->{$key} = $change['modified'];
        }

        $this->save();

        $rating_score = isset($changes['rating_score']['modified'])
            ? (int) $changes['rating_score']['modified']
            : null;

        resolve(ContentRatingService::class)->syncFromApprovedComment($this, $rating_score);

        event(new ModificationApproved($modification, $this));

        $modification->active = false;
        $modification->save();
    }

    public function setRatingScoreAttribute(mixed $value): void
    {
        $this->pending_rating_score = $value !== null && $value !== '' ? (int) $value : null;
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
