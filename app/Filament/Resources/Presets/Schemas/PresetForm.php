<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Presets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class PresetForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        self::configureForm($schema);

        return $schema
            ->components([
                Select::make('entity_id')
                    ->relationship('entity', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                Select::make('template_id')
                    ->relationship('template', 'name'),
                Toggle::make('is_deleted')
                    ->required(),
            ]);
    }
}
