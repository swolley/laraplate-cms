<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contributors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class ContributorForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        return self::configureForm(
            $schema->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('components')
                    ->required(),
                Toggle::make('is_deleted')
                    ->required(),
            ]),
        );
    }
}
