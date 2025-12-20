<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Modules\Cms\Database\Factories\AuthorFactory;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasPath;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Helpers\HasTranslatedDynamicContents;
use Modules\Cms\Models\Pivot\Authorable;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasVersions;
use Modules\Core\Helpers\SoftDeletes;
use Override;
use Spatie\MediaLibrary\HasMedia as IMediable;

/**
 * @mixin IdeHelperAuthor
 */
final class Author extends Model implements IMediable
{
    // region Traits
    use HasFactory;
    use HasMultimedia;
    use HasPath;
    use HasSlug;
    use HasTags;
    use HasTranslatedDynamicContents {
        HasTranslatedDynamicContents::getRules as private getRulesTranslatedDynamicContents;
        HasTranslatedDynamicContents::casts as private translatedDynamicContentsCasts;
    }
    use HasValidations {
        HasValidations::getRules as private getRulesTrait;
    }
    use HasVersions;
    use SoftDeletes;
    // endregion

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'user_id',
        'user',
        'created_at',
        'updated_at',
    ];

    private ?User $tempUser = null;

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(user_class());
    }

    /**
     * The contents that belong to the author.
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'authorables')->using(Authorable::class)->withTimestamps();
    }

    // Save method to handle user creation/updating
    #[Override]
    public function save(array $options = []): bool
    {
        if ($this->tempUser instanceof User && $this->tempUser->isDirty()) {
            $this->tempUser->save();
            $this->user_id = $this->tempUser->id;
            $this->tempUser = null;
            $this->load('user');
        } elseif ($this->user && $this->user->isDirty()) {
            $this->user->save();
        }

        return parent::save($options);
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $fields = $this->getRulesTranslatedDynamicContents();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:authors,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:authors,name,' . $this->id],
        ]);

        return $rules;
    }

    public function getPathPrefix(): string
    {
        return $this->entity?->slug ?? '';
    }

    #[Override]
    public function getPath(): ?string
    {
        return null;
    }

    protected static function booted(): void
    {
        self::addGlobalScope(static fn (Builder $query) => $query->with('user'));
    }

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }

    protected function casts(): array
    {
        return array_merge($this->translatedDynamicContentsCasts(), [
            'user_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ]);
    }

    protected function slugFields(): array
    {
        // Use name from translation
        return [...$this->dynamicSlugFields(), 'name'];
    }

    protected function getCanLoginAttribute(): bool
    {
        return $this->user !== null || $this->tempUser instanceof User;
    }

    protected function getIsSignatureAttribute(): bool
    {
        return ! $this->getCanLoginAttribute();
    }

    private function isUserAttribute(): bool
    {
        return $this->getCanLoginAttribute();
    }
}
