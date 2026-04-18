<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Database\Factories\CategoryFactory;
use Modules\Cms\Helpers\HasMultimedia;
use Modules\Cms\Helpers\HasTags;
use Modules\Cms\Models\Pivot\Categorizable;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Helpers\HasTranslatedDynamicContents;
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
    use HasTranslatedDynamicContents {
        HasTranslatedDynamicContents::getRules as private getRulesDynamicContents;
        HasTranslatedDynamicContents::toArray as private translatedDynamicContentsToArray;
        HasTranslatedDynamicContents::casts as private translatedDynamicContentsCasts;
    }

    public function __construct(array $attributes = [])
    {
        return parent::__construct($attributes);
        $this->fillable[] = 'logo';
        $this->fillable[] = 'logo_full';
    }
    // endregion

    #[Override]
    public static function getEntityModelClass(): string
    {
        return Entity::class;
    }

    /**
     * The contents that belong to the category.
     *
     * @return BelongsToMany<Content,Category,Categorizable,'pivot'>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'categorizables')->using(Categorizable::class)->withTimestamps();
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
