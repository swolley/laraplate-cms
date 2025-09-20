<?php

namespace Modules\Cms\Filament\Resources\Authors\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Author;
use Modules\Core\Filament\Utils\HasTable;

final class AuthorsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    IconColumn::make('user.id')
                        ->label('Type')
                        ->trueIcon('heroicon-o-user')
                        ->falseIcon('heroicon-o-pencil')
                        ->falseColor('gray')
                        ->state(fn(Author $record) => $record->user?->id !== null)
                        ->alignCenter()
                        ->tooltip(
                            fn(Author $record) => $record->user !== null
                                ? sprintf("User (#%d: %s)", $record->user->id, $record->user->name) :
                                'Author'
                        )
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->grow(false),
                    ImageColumn::make('user.cover')
                        ->label('Avatar')
                        ->circular()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->grow(false),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->grow(true),
                    TextColumn::make('components.email')
                        ->label('Email')
                        ->searchable()
                        ->sortable()
                        ->toggleable()
                        ->grow(true),
                ]);
            },
        );
    }
}
