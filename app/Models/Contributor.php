<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Contracts\Taggable;
use Modules\CMS\Database\Factories\ContributorFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Helpers\HasMultimedia;
use Modules\CMS\Helpers\HasTags;
use Modules\CMS\Models\Pivot\Contributable;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Helpers\HasPath;
use Modules\Core\Helpers\HasTranslatedDynamicContents;
use Modules\Core\Overrides\Model;
use Override;
use Spatie\MediaLibrary\HasMedia as IMediable;

/**
 * @mixin \Eloquent
 * @mixin IdeHelperContributor
 */
final class Contributor extends Model implements IMediable, Taggable
{
    // region Traits
    use HasMultimedia;
    use HasPath;
    use HasTags;
    use HasTranslatedDynamicContents {
        HasTranslatedDynamicContents::getRules as private getRulesTranslatedDynamicContents;
        HasTranslatedDynamicContents::casts as private translatedDynamicContentsCasts;
    }
    // endregion

    #[Override]
    protected $table = CMSTables::Contributors->value;

    #[Override]
    protected $fillable = [
        'name',
    ];

    #[Override]
    protected $hidden = [
        'user_id',
        'user',
        'created_at',
        'updated_at',
    ];

    private ?User $tempUser = null;

    public static function getEntityModelClass(): string
    {
        return Entity::class;
    }

    /**
     * Hold a not-yet-persisted user to attach on first save (e.g. create user and contributor in one flow).
     */
    public function setTempUser(?User $user): void
    {
        $this->tempUser = $user;
    }

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(user_class());
    }

    /**
     * The contents that belong to the contributor.
     *
     * @return BelongsToMany<Content,Contributor,Contributable,'pivot'>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, CMSTables::Contributables->value)->using(Contributable::class)->withTimestamps();
    }

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
        $rules = parent::getRules();
        $fields = $this->getRulesTranslatedDynamicContents();
        $rules[Model::DEFAULT_RULE] = array_merge($rules[Model::DEFAULT_RULE], $fields);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:'.CMSTables::Contributors->value.',name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:'.CMSTables::Contributors->value.',name,' . $this->id],
        ]);

        return $rules;
    }

    public function getPathPrefix(): string
    {
        $entity = $this->entity;

        return $entity !== null ? $entity->slug : '';
    }

    #[Override]
    public function getPath(): ?string
    {
        return null;
    }

    protected static function getEntityType(): IDynamicEntityTypable
    {
        return EntityType::Contributors;
    }

    protected static function booted(): void
    {
        self::addGlobalScope(static fn (Builder $query) => $query->with('user'));
    }

    protected static function newFactory(): ContributorFactory
    {
        return ContributorFactory::new();
    }

    protected function casts(): array
    {
        return array_merge($this->translatedDynamicContentsCasts(), [
            'user_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ]);
    }

    protected function slugPlaceholders(): array
    {
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

    protected function isUserAttribute(): bool
    {
        return $this->getCanLoginAttribute();
    }
}
