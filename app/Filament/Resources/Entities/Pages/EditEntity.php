<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Entities\EntityResource;

class EditEntity extends EditRecord
{
    protected static string $resource = EntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
