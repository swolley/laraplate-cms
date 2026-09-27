<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Modules\Core\Filament\Utils\HasForm;

final class CommentForm
{
    use HasForm;

    public static function configure(Schema $schema): Schema
    {
        self::configureForm($schema);

        return $schema
            ->components([
                Select::make('content_id')
                    ->relationship('content', 'id')
                    ->disabled(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Textarea::make('body')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
            ]);
    }
}
