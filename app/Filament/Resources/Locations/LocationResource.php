<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Cms\Filament\Resources\Locations\Pages\CreateLocation;
use Modules\Cms\Filament\Resources\Locations\Pages\EditLocation;
use Modules\Cms\Filament\Resources\Locations\Pages\ListLocations;
use Modules\Cms\Filament\Resources\Locations\Schemas\LocationForm;
use Modules\Cms\Filament\Resources\Locations\Tables\LocationsTable;
use Modules\Cms\Models\Location;
use UnitEnum;

final class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    protected static ?int $navigationSort = 7;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/locations';
    }

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
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
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
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
