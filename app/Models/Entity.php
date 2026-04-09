<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Modules\Cms\Casts\EntityType;
use Modules\Core\Models\Entity as CoreEntity;
use Modules\Core\Models\Pivot\Presettable;
use Override;

final class Entity extends CoreEntity
{
    /**
     * The contents that belong to the entity.
     *
     * @return HasManyThrough<Content>
     */
    public function contents(): HasManyThrough
    {
        return $this->hasManyThrough(
            Content::class,
            Presettable::class,
            'entity_id',      // foreign key on presettables pointing to entities
            'presettable_id', // foreign key on contents pointing to presettables
        );
    }

    /**
     * The categories that belong to the entity.
     *
     * @return HasManyThrough<Category>
     */
    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(
            Category::class,
            Presettable::class,
            'entity_id',      // foreign key on presettables pointing to entities
            'presettable_id', // foreign key on categories pointing to presettables
        );
    }

    #[Override]
    protected static function getEntityTypeEnumClass(): string
    {
        return EntityType::class;
    }
}
