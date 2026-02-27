<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Filament\Resources\Entities\EntityResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Modules\Cms\Models\Entity;

final class ListEntities extends ListRecords
{
    use HasRecords;

    protected static string $resource = EntityResource::class;

    public function getTabs(): array
    {
        // $cache_key = 'filament_cms_entities_tabs_' . Entity::class;

        // $counts = Cache::remember($cache_key, config('core.filament.tabs_counts_ttl_seconds'), function () {
        $counts_by_type = Entity::query()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->all();

        $counts = array_merge(['all' => (int) array_sum($counts_by_type)], $counts_by_type);
        // });

        if (count($counts) < 2) {
            return [];
        }

        $tabs = [
            'all' => Tab::make('All')->badge($counts['all']),
        ];

        foreach (EntityType::cases() as $type) {
            $totals = (int) ($counts[$type->value] ?? 0);

            if ($totals === 0) {
                continue;
            }

            $tabs[$type->value] = Tab::make(ucfirst($type->value))
                ->badge($totals)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', $type));
        }

        return $tabs;
    }
}
