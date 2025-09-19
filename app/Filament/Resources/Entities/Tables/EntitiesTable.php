<?php

namespace Modules\Cms\Filament\Resources\Entities\Tables;

use \Override;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Entity;
use Modules\Core\Filament\Utils\BaseTable;

final class EntitiesTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Entity::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    IconColumn::make('is_active')
                        ->boolean(),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('slug')
                        ->searchable(),
                    TextColumn::make('type')
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
