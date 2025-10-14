<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
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
        $tabs = [];

        foreach (Entity::query()->get(['id', 'name']) as $entity) {
            $tabs[$entity->name] = Tab::make($entity->name)
                ->badge(Preset::query()->where('entity_id', $entity->id)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('entity_id', $entity->id));
        }

        return array_merge([
            'all' => Tab::make('All')->badge(Preset::query()->count()),
        ], $tabs);
    }
}
