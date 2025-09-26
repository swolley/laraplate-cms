<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('type'),
                TextInput::make('order_column')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_deleted')
                    ->required(),
            ]);
    }
}
