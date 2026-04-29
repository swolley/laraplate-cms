<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Locations;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\CMS\Filament\Resources\Locations\Pages\CreateLocation;
use Modules\CMS\Filament\Resources\Locations\Pages\EditLocation;
use Modules\CMS\Filament\Resources\Locations\Pages\ListLocations;
use Modules\CMS\Filament\Resources\Locations\Schemas\LocationForm;
use Modules\CMS\Filament\Resources\Locations\Tables\LocationsTable;
use Modules\CMS\Models\Location;
use Override;
use UnitEnum;

final class LocationResource extends Resource
{
    #[Override]
    protected static ?string $model = Location::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    #[Override]
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
