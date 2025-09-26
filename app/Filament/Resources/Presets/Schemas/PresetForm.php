<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PresetForm
{
    public static function configure(Schema $schema): Schema
    {
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
