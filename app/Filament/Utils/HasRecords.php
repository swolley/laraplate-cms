<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Utils;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Core\Filament\Utils\HasRecords as BaseHasRecords;
use ReflectionClass;

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

        $table = new ReflectionClass($model)->newInstanceWithoutConstructor()->getTable();
        $entities = $model::fetchAvailableEntities(EntityType::tryFrom($table));

        if ($entities->count() < 2) {
            return [];
        }

        $cache_key = 'filament_cms_tabs_' . $model . '_' . $entities->pluck('id')->sort()->values()->implode(',');

        $counts = Cache::remember($cache_key, config('core.filament.tabs_counts_ttl_seconds'), function () use ($model): array {
            /** @var class-string<Model> $model */
            $counts_by_entity = $model::query()
                ->withoutGlobalScopes(['global_ordered'])
                ->selectRaw('entity_id, count(*) as count')
                ->groupBy('entity_id')
                ->pluck('count', 'entity_id')
                ->all();

            return array_merge(['all' => (int) array_sum($counts_by_entity)], $counts_by_entity);
        });

        if (count($counts) < 2) {
            return [];
        }

        $tabs = [
            'all' => Tab::make('All')->badge($counts['all']),
        ];

        foreach ($entities as $entity) {
            $totals = (int) ($counts[$entity->id] ?? 0);

            if ($totals === 0) {
                continue;
            }

            $tabs[$entity->name] = Tab::make($entity->name)
                ->badge($totals)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('entity_id', $entity->id));
        }

        return $tabs;
    }
}
