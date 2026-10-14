<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
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
                // A new content goes last whatever is typed (`sort_when_creating`), so the position is edited only
                // once the content exists.
                TextInput::make('order_column')
                    ->label('Position')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->hiddenOn('create')
                    ->helperText('Position in the list; on a tie the older content comes first. Dragging the rows of the list rewrites the positions.'),
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
