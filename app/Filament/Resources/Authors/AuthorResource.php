<?php

namespace Modules\Cms\Filament\Resources\Authors;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Cms\Filament\Resources\Authors\Pages\CreateAuthor;
use Modules\Cms\Filament\Resources\Authors\Pages\EditAuthor;
use Modules\Cms\Filament\Resources\Authors\Pages\ListAuthors;
use Modules\Cms\Filament\Resources\Authors\Schemas\AuthorForm;
use Modules\Cms\Filament\Resources\Authors\Tables\AuthorsTable;
use Modules\Cms\Models\Author;
use UnitEnum;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    protected static ?int $navigationSort = 1;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/authors';
    }

    public static function form(Schema $schema): Schema
    {
        return AuthorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuthorsTable::configure($table);
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
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
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
