<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Fields\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Field;
use Modules\Core\Filament\Utils\HasTable;

final class FieldsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns): void {
                $columns->unshift(...[
                    ToggleColumn::make('is_active')
                        // ->searchable()
                        ->alignCenter()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('type')
                        ->searchable(),
                    TextColumn::make('options')
                        ->formatStateUsing(function (Field $record) {
                            $options = json_decode((string) $record->options, true);
                            $string = '';

                            foreach ($options as $key => $value) {
                                $string .= sprintf(
                                    '<div class="flex justify-between">
                                    <span>%s:</span>
                                    <span>%s</span>
                                    </div>',
                                    $key,
                                    $value,
                                );
                            }

                            return "<div class=\"space-y-1\">{$string}</div";
                        })
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->html(),
                    IconColumn::make('is_slug')
                        ->boolean()
                        ->alignCenter()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: true),
                ]);
            },
        );
    }
}
