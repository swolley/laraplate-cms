<?php

namespace Modules\Cms\Filament\Resources\Presets;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Cms\Filament\Resources\Presets\Pages\CreatePreset;
use Modules\Cms\Filament\Resources\Presets\Pages\EditPreset;
use Modules\Cms\Filament\Resources\Presets\Pages\ListPresets;
use Modules\Cms\Filament\Resources\Presets\Schemas\PresetForm;
use Modules\Cms\Filament\Resources\Presets\Tables\PresetsTable;
use Modules\Cms\Models\Preset;
use UnitEnum;

class PresetResource extends Resource
{
    protected static ?string $model = Preset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

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
        return PresetsTable::configure($table);
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
