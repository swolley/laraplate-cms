<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Cms\Models\Category;
use Modules\Core\Filament\Utils\HasTable;

final class CategoriesTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $default_columns): void {
                $default_columns->unshift(...[
                    ToggleColumn::make('is_active')
                        ->alignCenter()
                        ->grow(false),
                    TextColumn::make('entity.name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('preset.name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->state(fn (Category $record): string => Str::repeat('&nbsp;', $record->ancestors->count() * 4) . $record->name)
                        ->html(),
                    TextColumn::make('path')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('parent_id')
                        ->numeric()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
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
