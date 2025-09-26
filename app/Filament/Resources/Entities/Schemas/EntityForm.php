<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\Cms\Casts\EntityType;

class EntityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('type')
                    ->options(EntityType::class)
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('locked_at'),
                DateTimePicker::make('locked_user_id'),
                Toggle::make('is_locked')
                    ->required(),
            ]);
    }
}
