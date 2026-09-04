<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Entities\Schemas;

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
                // Lock state is coordination metadata, not editable data: it is taken and
                // released through the lock actions, never by typing. `locked_user_id` used to
                // be rendered as a date picker because the column was wrongly declared a
                // timestamp, and `is_locked` is now computed rather than stored.
            ]);
    }
}
