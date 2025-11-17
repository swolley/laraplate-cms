<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entity_id')
                    ->relationship('entity', 'name')
                    ->required(),
                Select::make('presettable_id')
                    ->relationship('presettable', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('components')
                    ->required(),
                TextInput::make('slug')
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
                DateTimePicker::make('valid_from'),
                DateTimePicker::make('valid_to'),
            ]);
    }
}
