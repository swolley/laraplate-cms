<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\CMS\Filament\Resources\Contents\Pages\CreateContent;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Filament\Resources\Contents\Pages\ListContents;
use Modules\CMS\Filament\Resources\Contents\Schemas\ContentForm;
use Modules\CMS\Filament\Resources\Contents\Tables\ContentsTable;
use Modules\CMS\Models\Content;
use Override;
use UnitEnum;

final class ContentResource extends Resource
{
    #[Override]
    protected static ?string $model = Content::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencil;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    #[Override]
    protected static ?int $navigationSort = 2;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/contents';
    }

    public static function form(Schema $schema): Schema
    {
        return ContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentsTable::configure($table)
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'presettable.entity',
                    'presettable.preset',
                    'media',
                ]),
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
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
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
