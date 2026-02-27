<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Cms\Filament\Utils\HasTable;
use Modules\Cms\Models\Preset;

final class PresetsTable
{
    use HasTable;

    public static function configure(Table $table): Table
    {
        return self::configureTable(
            table: $table,
            columns: static function (Collection $columns): void {
                $columns->unshift(...[
                    TextColumn::make('entity.name')
                        ->searchable(),
                    TextColumn::make('name')
                        ->searchable(),
                    TextColumn::make('template.name')
                        ->searchable(),
                ]);
            },
            actions: static function (Collection $actions): void {
                $actions->push(
                    Action::make('migrate')
                        ->label('Migrate Contents to the last version')
                        ->modal(true)
                        ->modalDescription(static function (Preset $record): string {
                            $contents = $record->contents()->count();

                            return "Are you sure you want to migrate {$contents} contents to the last version?";
                        })
                        ->modalSubmitActionLabel('Yes, migrate')
                        ->modalCancelActionLabel('No, cancel')
                        ->action(static function (Preset $record): void {
                            $record->migrateContentsToLastVersion();
                        })
                        ->icon(Heroicon::ArrowPath)
                        ->visible(static function (Preset $record): bool {
                            return $record->contents()->whereHas('presettable', function (Builder $query) use ($record): void {
                                $query->where('version', '<', $record->activePresettable()->version);
                            })->exists();
                        }),
                );
            },
        );
    }
}
