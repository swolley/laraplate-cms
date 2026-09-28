<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Presets\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Presets\PresetResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\Core\Filament\Utils\ReportsApprovalOutcome;
use Override;

final class EditPreset extends EditRecord
{
    use HasCloseOrCancelFormAction;
    use ReportsApprovalOutcome;

    #[Override]
    protected static string $resource = PresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            self::approvalAwareDeleteAction(),
            self::approvalAwareForceDeleteAction(),
            self::approvalAwareRestoreAction(),
        ];
    }
}
