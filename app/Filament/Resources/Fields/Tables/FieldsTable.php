<?php

namespace Modules\Cms\Filament\Resources\Fields\Tables;

use \Override;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Field;
use Modules\Core\Filament\Utils\BaseTable;

final class FieldsTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Field::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('type')
                        ->searchable(),
                    IconColumn::make('is_slug')
                        ->boolean(),
                ]);
            },
        );
    }
}
