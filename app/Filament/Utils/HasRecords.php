<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Utils;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Core\Filament\Utils\HasRecords as BaseHasRecords;

trait HasRecords
{
    use BaseHasRecords;

    public function getTabs(): array
    {
        $model = self::getResource()::getModel();

        if ($model === null) {
            return [];
        }

        if (! class_uses_trait($model, HasDynamicContents::class)) {
            return [];
        }

        $entities = $model::fetchAvailableEntities(EntityType::tryFrom(new $model()->getTable()));

        if ($entities->count() < 2) {
            return [];
        }

        $tabs = [
            'all' => Tab::make('All')->badge($model::query()->count()),
        ];

        foreach ($entities as $entity) {
            $tabs[$entity->name] = Tab::make($entity->name)
                ->badge($model::query()->where('entity_id', $entity->id)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('entity_id', $entity->id));
        }

        return $tabs;
    }
}
