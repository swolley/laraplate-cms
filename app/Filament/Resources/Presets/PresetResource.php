<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Presets;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Filament\Resources\Presets\Pages\CreatePreset;
use Modules\CMS\Filament\Resources\Presets\Pages\EditPreset;
use Modules\CMS\Filament\Resources\Presets\Pages\ListPresets;
use Modules\CMS\Filament\Resources\Presets\Schemas\PresetForm;
use Modules\CMS\Filament\Resources\Presets\Tables\PresetsTable;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Preset;
use Override;
use UnitEnum;

final class PresetResource extends Resource
{
    #[Override]
    protected static ?string $model = Preset::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    #[Override]
    protected static ?int $navigationSort = 5;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/presets';
    }

    public static function form(Schema $schema): Schema
    {
        return PresetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresetsTable::configure($table)
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query
                ->with(['entity', 'template'])
                ->whereHas('entity', static fn (Builder $query): Builder => $query->whereIn(
                    (new Entity())->qualifyColumn('type'),
                    EntityType::values(),
                )),
            );
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresets::route('/'),
            'create' => CreatePreset::route('/create'),
            'edit' => EditPreset::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
