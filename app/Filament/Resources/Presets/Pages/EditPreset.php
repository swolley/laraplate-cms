<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Presets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Presets\PresetResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Override;

final class EditPreset extends EditRecord
{
    use HasCloseOrCancelFormAction;

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
