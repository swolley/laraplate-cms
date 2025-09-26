<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates;

use BackedEnum;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Cms\Filament\Resources\Templates\Pages\CreateTemplate;
use Modules\Cms\Filament\Resources\Templates\Pages\EditTemplate;
use Modules\Cms\Filament\Resources\Templates\Pages\ListTemplates;
use Modules\Cms\Filament\Resources\Templates\Schemas\TemplateForm;
use Modules\Cms\Filament\Resources\Templates\Tables\TemplatesTable;
use Modules\Cms\Models\Template;
use UnitEnum;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|UnitEnum|null $navigationGroup = 'Cms';

    protected static ?int $navigationSort = 9;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/templates';
    }

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configure($table);
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
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
