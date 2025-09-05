<?php

namespace Modules\Cms\Filament\Resources\Authors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('entity_id')
                    ->relationship('entity', 'name')
                    ->required(),
                Select::make('preset_id')
                    ->relationship('preset', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('components')
                    ->required(),
                Toggle::make('is_deleted')
                    ->required(),
            ]);
    }
}
