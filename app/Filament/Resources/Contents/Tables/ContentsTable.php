<?php

namespace Modules\Cms\Filament\Resources\Contents\Tables;

use \Override;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Content;
use Modules\Core\Filament\Utils\BaseTable;

final class ContentsTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Content::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $default_columns) {
                $default_columns->unshift(...[
                    TextColumn::make('entity.name')
                        ->searchable(),
                    TextColumn::make('preset.name')
                        ->searchable(),
                    TextColumn::make('title')
                        ->searchable(),
                    TextColumn::make('slug')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
