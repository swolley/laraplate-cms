<?php

namespace Modules\Cms\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entity_id')
                    ->relationship('entity', 'name')
                    ->required(),
                Select::make('preset_id')
                    ->relationship('preset', 'name')
                    ->required(),
                TextInput::make('parent_id')
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('components')
                    ->required(),
                TextInput::make('persistence')
                    ->numeric(),
                TextInput::make('logo'),
                TextInput::make('logo_full'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('order_column')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_deleted')
                    ->required(),
                DateTimePicker::make('locked_at'),
                DateTimePicker::make('locked_user_id'),
                Toggle::make('is_locked')
                    ->required(),
                DateTimePicker::make('valid_from')
                    ->required(),
                DateTimePicker::make('valid_to'),
            ]);
    }
}
