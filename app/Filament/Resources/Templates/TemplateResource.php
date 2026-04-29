<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Templates;

use BackedEnum;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\CMS\Filament\Resources\Templates\Pages\CreateTemplate;
use Modules\CMS\Filament\Resources\Templates\Pages\EditTemplate;
use Modules\CMS\Filament\Resources\Templates\Pages\ListTemplates;
use Modules\CMS\Filament\Resources\Templates\Schemas\TemplateForm;
use Modules\CMS\Filament\Resources\Templates\Tables\TemplatesTable;
use Modules\CMS\Models\Template;
use Override;
use UnitEnum;

final class TemplateResource extends Resource
{
    #[Override]
    protected static ?string $model = Template::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    #[Override]
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
