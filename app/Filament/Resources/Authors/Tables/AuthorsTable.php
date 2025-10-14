<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Authors\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Modules\Cms\Models\Author;
use Modules\Core\Filament\Utils\HasTable;

final class AuthorsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: function (Collection $columns): void {
                $columns->unshift(...[
                    IconColumn::make('user.id')
                        ->label('Type')
                        ->trueIcon('heroicon-o-user')
                        ->falseIcon('heroicon-o-pencil')
                        ->falseColor('gray')
                        ->state(fn (Author $record): bool => $record->user?->id !== null)
                        ->alignCenter()
                        ->tooltip(
                            fn (Author $record): string => $record->user !== null
                                ? sprintf('User (#%d: %s)', $record->user->id, $record->user->name)
                                : 'Author',
                        )
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->grow(false)
                        ->width(100),
                    ImageColumn::make('user.cover')
                        ->label('Avatar')
                        ->circular()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->grow(false)
                        ->defaultImageUrl(function (Author $record): UrlGenerator|string {
                            $hash = abs(crc32($record->name));

                            $hue = $hash % 360;
                            $saturation = 60 + ($hash % 20); // 60-80%
                            $lightness = 40 + ($hash % 20); // 40-60%

                            $h = $hue / 360;
                            $s = $saturation / 100;
                            $l = $lightness / 100;

                            $c = (1 - abs(2 * $l - 1)) * $s;
                            $x = $c * (1 - abs(fmod($h * 6, 2) - 1));
                            $m = $l - $c / 2;

                            if ($h < 1 / 6) {
                                $r = $c;
                                $g = $x;
                                $b = 0;
                            } elseif ($h < 2 / 6) {
                                $r = $x;
                                $g = $c;
                                $b = 0;
                            } elseif ($h < 3 / 6) {
                                $r = 0;
                                $g = $c;
                                $b = $x;
                            } elseif ($h < 4 / 6) {
                                $r = 0;
                                $g = $x;
                                $b = $c;
                            } elseif ($h < 5 / 6) {
                                $r = $x;
                                $g = 0;
                                $b = $c;
                            } else {
                                $r = $c;
                                $g = 0;
                                $b = $x;
                            }

                            $r = round(($r + $m) * 255);
                            $g = round(($g + $m) * 255);
                            $b = round(($b + $m) * 255);

                            $hex = sprintf('%02x%02x%02x', $r, $g, $b);

                            return url("https://ui-avatars.com/api/?name={$record->name[0]}&color=FFFFFF&background={$hex}");
                        })
                        ->extraImgAttributes(['loading' => 'lazy']),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->grow(true),
                    TextColumn::make('components.email')
                        ->label('Email')
                        ->searchable()
                        ->sortable()
                        ->toggleable()
                        ->grow(true),
                ]);
            },
        );
    }
}
