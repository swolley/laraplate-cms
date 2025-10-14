<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_deleted')
                    ->required(),
            ]);
    }
}
