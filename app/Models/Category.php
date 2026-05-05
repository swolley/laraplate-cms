<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Database\Factories\CategoryFactory;
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
 * @mixin IdeHelperCategory
 */
final class Category extends Taxonomy implements IMediable, Sortable
{
    // region Traits
    use HasMultimedia;
    use HasTags;

    public function __construct(array $attributes = [])
    {
        array_push($this->fillable, 'logo', 'logo_full');

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
     * @return BelongsToMany<Content,Category,Categorizable,'pivot'>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'categorizables', 'taxonomy_id', 'content_id')
            ->using(Categorizable::class)
            ->withTimestamps();
    }

    #[Override]
    protected static function getEntityType(): IDynamicEntityTypable
    {
        return EntityType::CATEGORIES;
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
