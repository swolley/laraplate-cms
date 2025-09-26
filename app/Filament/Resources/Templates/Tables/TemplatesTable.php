<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Core\Filament\Utils\HasTable;

final class TemplatesTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns): void {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable(),
                ]);
            },
        );
    }
}
