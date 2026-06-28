<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\CMS\Contracts\Taggable;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Database\Factories\CategoryFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Helpers\HasMultimedia;
use Modules\CMS\Helpers\HasTags;
use Modules\CMS\Models\Pivot\Categorizable;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Models\Taxonomy;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\MediaLibrary\HasMedia as IMediable;

/**
 * @property-read string|null $name
 * @phpstan-use HasMultimedia<Category>
 * @mixin \Eloquent
 * @mixin IdeHelperCategory
 */
final class Category extends Taxonomy implements IMediable, Sortable, Taggable
{
    // region Traits
    use HasMultimedia;
    use HasTags;

    public function __construct(array $attributes = [])
    {
        $this->fillable[] = 'logo';
        $this->fillable[] = 'logo_full';
        parent::__construct($attributes);
    }
    // endregion

    #[Override]
    public static function getEntityModelClass(): string
    {
        return Entity::class;
    }

    #[Override]
    public static function getPresettableClass(): string
    {
        return Presettable::class;
    }

    /**
     * The contents that belong to the category.
     *
     * @return BelongsToMany<Content, $this, Categorizable, 'pivot'>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, CMSTables::Categorizables->value, 'taxonomy_id', 'content_id')
            ->using(Categorizable::class)
            ->withTimestamps();
    }

    #[Override]
    protected static function getEntityType(): IDynamicEntityTypable
    {
        return EntityType::Categories;
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
