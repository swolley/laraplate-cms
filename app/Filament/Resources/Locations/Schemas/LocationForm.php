<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('address'),
                TextInput::make('city'),
                TextInput::make('province'),
                TextInput::make('country')
                    ->required(),
                TextInput::make('postcode'),
                TextInput::make('zone'),
                TextInput::make('geolocation'),
                Toggle::make('is_deleted')
                    ->required(),
                DateTimePicker::make('locked_at'),
                DateTimePicker::make('locked_user_id'),
                Toggle::make('is_locked')
                    ->required(),
            ]);
    }
}
