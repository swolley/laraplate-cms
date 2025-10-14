<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Location;
use Modules\Core\Filament\Utils\HasTable;

final class LocationsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns): void {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable()
                        ->limit(),
                    TextColumn::make('slug')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('address')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('city')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('province')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('country')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('postcode')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('zone')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('geolocation')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('map')
                        ->formatStateUsing(fn (Location $record): string => "<div class=\"space-y-1\">{$record->geolocation}->getLatitude() {$record->geolocation}->getLongitude()</div>")
                        ->html()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
