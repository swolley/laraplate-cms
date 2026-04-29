<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Presets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Presets\PresetResource;
use Override;

final class CreatePreset extends CreateRecord
{
    #[Override]
    protected static string $resource = PresetResource::class;
}
