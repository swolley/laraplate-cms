<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contents\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Media;
use Modules\Core\Filament\Utils\HasTable;

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
                        ->state(static function (Content $record) {
                            $images = collect($record->cover ? [$record->cover?->getUrl('thumb-low')] : []);

                            return $images->merge($record->getMedia('images')->map(static fn (Media $media): string => $media->getUrl('thumb-low')))->unique();
                        })
                        ->stacked()
                        ->limit(3)
                        ->limitedRemainingText()
                        ->extraImgAttributes(['loading' => 'lazy'])
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->extraImgAttributes(['loading' => 'lazy']),
                ]);
            },
        );
    }
}
