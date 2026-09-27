<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Comments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class CommentsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $columns): void {
                $columns->unshift(...[
                    TextColumn::make('content.title')
                        ->label('Content')
                        ->limit(60),
                    TextColumn::make('user.name')
                        ->label('Author')
                        ->searchable(),
                    TextColumn::make('body')
                        ->limit(80)
                        ->wrap(),
                ]);
            },
        );
    }
}
