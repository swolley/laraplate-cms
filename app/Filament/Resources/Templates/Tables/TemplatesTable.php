<?php

namespace Modules\Cms\Filament\Resources\Templates\Tables;

use \Override;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Template;
use Modules\Core\Filament\Utils\BaseTable;

final class TemplatesTable extends BaseTable
{
    #[Override]
    protected function getModel(): string
    {
        return Template::class;
    }

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns) {
                $columns->unshift(...[
                    TextColumn::make('name')
                        ->searchable(),
                ]);
            },
        );
    }
}
