<?php

namespace Modules\Cms\Filament\Resources\Presets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Presets\PresetResource;

class ListPresets extends ListRecords
{
    protected static string $resource = PresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
