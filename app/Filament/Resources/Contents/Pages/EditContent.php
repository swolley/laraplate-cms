<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contents\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Contents\ContentResource;

final class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
