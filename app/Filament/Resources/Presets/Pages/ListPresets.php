<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Cms\Filament\Resources\Presets\PresetResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;

final class ListPresets extends ListRecords
{
    use HasRecords;

    protected static string $resource = PresetResource::class;

    public function getTabs(): array
    {
        $entities = Entity::query()->all()->keyBy('id');

        // $cache_key = 'filament_cms_presets_tabs_' . Preset::class;

        // $counts = Cache::remember($cache_key, config('core.filament.tabs_counts_ttl_seconds'), function () {
        $counts_by_entity = Preset::query()
            ->selectRaw('entity_id, count(*) as count')
            ->groupBy('entity_id')
            ->pluck('count', 'entity_id')
            ->all();

        $counts = array_merge(['all' => (int) array_sum($counts_by_entity)], $counts_by_entity);
        // });

        if (count($counts) < 2) {
            return [];
        }

        $tabs = [
            'all' => Tab::make('All')->badge($counts['all']),
        ];

        foreach ($counts_by_entity as $entity_id => $count) {
            $totals = (int) ($counts[$entity_id] ?? 0);

            if ($totals === 0) {
                continue;
            }

            $entity_name = $entities[$entity_id]->name;
            $tabs[$entity_name] = Tab::make($entity_name)
                ->badge($totals)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('entity_id', $entity_id));
        }

        return $tabs;
    }
}
