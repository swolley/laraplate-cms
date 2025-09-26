<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class PresetsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns): void {
                $columns->unshift(...[
                    ToggleColumn::make('is_active')
                        ->grow(false)
                        ->alignCenter(),
                    TextColumn::make('entity.name')
                        ->searchable(),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('template.name')
                        ->searchable(),
                ]);
            },
            filters: function (Collection $default_filters): void {
                $default_filters->unshift(
                    TernaryFilter::make('is_active')
                        ->label('Active')
                        ->attribute('is_active')
                        ->nullable(),
                );
            },
        );
    }
}
