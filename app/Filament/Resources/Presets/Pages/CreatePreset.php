<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Presets\PresetResource;

final class CreatePreset extends CreateRecord
{
    protected static string $resource = PresetResource::class;
}
