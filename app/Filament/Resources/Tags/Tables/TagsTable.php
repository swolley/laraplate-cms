<?php

namespace Modules\Cms\Filament\Resources\Tags\Tables;

use \Override;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Tag;
use Modules\Core\Filament\Utils\BaseTable;

final class TagsTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Tag::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('slug')
                        ->searchable(),
                    TextColumn::make('type')
                        ->searchable(),
                ]);
            },
        );
    }
}
