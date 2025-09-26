<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Authors\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Authors\AuthorResource;

class EditAuthor extends EditRecord
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
