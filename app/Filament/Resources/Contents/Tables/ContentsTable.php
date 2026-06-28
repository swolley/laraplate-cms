<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\CMS\Filament\Utils\HasTable;
use Modules\CMS\Models\Content;
use Illuminate\Support\Collection as SupportCollection;

final class ContentsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $default_columns): void {
                $default_columns->unshift(...[
                    TextColumn::make('entity.name')
                        ->searchable()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('preset.name')
                        ->searchable()
                        ->grow(false)
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('title')
                        ->searchable()
                        ->limit(),
                    TextColumn::make('slug')
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    ImageColumn::make('media.images')
                        ->label('Images')
                        ->state(static function (Content $record): SupportCollection {
                            $urls = [];
                            $cover = $record->getFirstMedia('cover');

                            if ($cover !== null) {
                                $urls[] = $cover->getUrl('thumb-low');
                            }

                            foreach ($record->getMedia('images') as $media) {
                                $urls[] = $media->getUrl('thumb-low');
                            }

                            return collect($urls)->unique()->values();
                        })
                        ->stacked()
                        ->limit(3)
                        ->limitedRemainingText()
                        ->extraImgAttributes(['loading' => 'lazy'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->extraImgAttributes(['loading' => 'lazy']),
                ]);
            },
        )
            ->defaultSort('created_at', 'desc');
    }
}
