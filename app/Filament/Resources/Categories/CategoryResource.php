<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Cms\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\Cms\Filament\Resources\Categories\Pages\EditCategory;
use Modules\Cms\Filament\Resources\Categories\Pages\ListCategories;
use Modules\Cms\Filament\Resources\Categories\Schemas\CategoryForm;
use Modules\Cms\Filament\Resources\Categories\Tables\CategoriesTable;
use Modules\Cms\Models\Category;
use UnitEnum;

final class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    protected static ?int $navigationSort = 3;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/categories';
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
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
