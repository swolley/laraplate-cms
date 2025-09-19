<?php

namespace Modules\Cms\Filament\Resources\Presets\Tables;

use \Override;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Preset;
use Modules\Core\Filament\Utils\BaseTable;

final class PresetsTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Preset::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    IconColumn::make('is_active')
                        ->boolean(),
                    TextColumn::make('entity.name')
                        ->searchable(),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('template.name')
                        ->searchable(),
                ]);
            },
            filters: function (Collection $default_filters) {
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
