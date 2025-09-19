<?php

namespace Modules\Cms\Filament\Resources\Categories\Tables;

use \Override;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Category;
use Modules\Core\Filament\Utils\BaseTable;

final class CategoriesTable extends BaseTable
{
    #[\Override]
    protected function getModel(): string
    {
        return Category::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $default_columns) {
                $default_columns->unshift(...[
                    IconColumn::make('is_active')
                        ->alignCenter()
                        ->boolean(),
                    TextColumn::make('entity.name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('preset.name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('path')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('parent_id')
                        ->numeric()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('slug')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('persistence')
                        ->numeric()
                        ->alignRight()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('logo')
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('logo_full')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
