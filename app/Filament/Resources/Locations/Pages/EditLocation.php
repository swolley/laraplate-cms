<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Locations\LocationResource;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
