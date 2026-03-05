<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Presets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Presets\PresetResource;
use Override;

final class EditPreset extends EditRecord
{
    #[Override]
    protected static string $resource = PresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
