<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities;

use BackedEnum;
// use Filament\Resources\Resource;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Cms\Filament\Resources\Entities\Pages\CreateEntity;
use Modules\Cms\Filament\Resources\Entities\Pages\EditEntity;
use Modules\Cms\Filament\Resources\Entities\Pages\ListEntities;
use Modules\Cms\Filament\Resources\Entities\Schemas\EntityForm;
use Modules\Cms\Filament\Resources\Entities\Tables\EntitiesTable;
use Modules\Cms\Models\Entity;
use UnitEnum;

class EntityResource extends Resource
{
    protected static ?string $model = Entity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    protected static ?int $navigationSort = 4;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/entities';
    }

    public static function form(Schema $schema): Schema
    {
        return EntityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EntitiesTable::configure($table);
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
            'index' => ListEntities::route('/'),
            'create' => CreateEntity::route('/create'),
            'edit' => EditEntity::route('/{record}/edit'),
        ];
    }
}
