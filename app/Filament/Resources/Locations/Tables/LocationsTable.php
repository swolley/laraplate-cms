<?php

namespace Modules\Cms\Filament\Resources\Locations\Tables;

use \Override;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Location;
use Modules\Core\Filament\Utils\BaseTable;

final class LocationsTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Location::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('slug')
                        ->searchable(),
                    TextColumn::make('address')
                        ->searchable(),
                    TextColumn::make('city')
                        ->searchable(),
                    TextColumn::make('province')
                        ->searchable(),
                    TextColumn::make('country')
                        ->searchable(),
                    TextColumn::make('postcode')
                        ->searchable(),
                    TextColumn::make('zone')
                        ->searchable(),
                    TextColumn::make('geolocation'),

                ]);
            },
        );
    }
}
