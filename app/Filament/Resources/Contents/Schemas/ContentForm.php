<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class ContentForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        return self::configureForm(
            $schema->components([
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
                // Lock state is coordination metadata, not editable data: it is taken and
                // released through the lock actions, never by typing. `locked_user_id` used to
                // be rendered as a date picker because the column was wrongly declared a
                // timestamp, and `is_locked` is now computed rather than stored.
                DateTimePicker::make('valid_from'),
                DateTimePicker::make('valid_to'),
            ]),
        );
    }
}
