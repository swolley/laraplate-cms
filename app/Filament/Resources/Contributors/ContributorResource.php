<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contributors;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Cms\Filament\Resources\Contributors\Pages\CreateContributor;
use Modules\Cms\Filament\Resources\Contributors\Pages\EditContributor;
use Modules\Cms\Filament\Resources\Contributors\Pages\ListContributors;
use Modules\Cms\Filament\Resources\Contributors\Schemas\ContributorForm;
use Modules\Cms\Filament\Resources\Contributors\Tables\ContributorsTable;
use Modules\Cms\Models\Contributor;
use Override;
use UnitEnum;

final class ContributorResource extends Resource
{
    #[Override]
    protected static ?string $model = Contributor::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    #[Override]
    protected static ?int $navigationSort = 1;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/contributors';
    }

    public static function form(Schema $schema): Schema
    {
        return ContributorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContributorsTable::configure($table);
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
            'index' => ListContributors::route('/'),
            'create' => CreateContributor::route('/create'),
            'edit' => EditContributor::route('/{record}/edit'),
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
