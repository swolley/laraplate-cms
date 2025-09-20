<?php

namespace Modules\Cms\Filament\Resources\Fields\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class FieldsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    ToggleColumn::make('is_active')
                        ->searchable()
                        ->alignCenter()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->boolean(),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('type')
                        ->searchable(),
                    TextColumn::make('options')
                        ->toggleable(isToggledHiddenByDefault: false),
                    IconColumn::make('is_slug')
                        ->boolean()
                        ->alignCenter()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
