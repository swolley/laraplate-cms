<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Entities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\Core\Filament\Utils\HasForm;

final class EntityForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        self::configureForm($schema);

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
