<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Filament\Utils\HasTable;

final class EntitiesTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $columns): void {
                $columns->unshift(...[
                    TextColumn::make('type')
                        ->searchable()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('slug')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
