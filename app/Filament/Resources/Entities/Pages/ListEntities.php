<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Filament\Resources\Entities\EntityResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Modules\Cms\Models\Entity;

class ListEntities extends ListRecords
{
    use HasRecords;

    protected static string $resource = EntityResource::class;

    public function getTabs(): array
    {
        $tabls = [
            'all' => Tab::make('All')->badge(Entity::query()->count()),
        ];

        foreach (EntityType::cases() as $type) {
            $tabls[$type->value] = Tab::make($type->value)
                ->badge(Entity::query()->where('type', $type)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', $type));
        }

        return $tabls;
    }
}
