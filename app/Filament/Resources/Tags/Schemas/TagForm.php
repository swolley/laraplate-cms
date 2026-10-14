<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class TagForm
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
                TextInput::make('type'),
                // A new tag goes last whatever is typed (`sort_when_creating`), so the position is edited only
                // once the tag exists.
                TextInput::make('order_column')
                    ->label('Position')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->hiddenOn('create')
                    ->helperText('Position in the list; on a tie the older tag comes first. Dragging the rows of the list rewrites the positions.'),
            ]);
    }
}
