<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\Core\Filament\Utils\HasFilamentFormDataSanitizer;
use Modules\Core\Filament\Utils\HasRecordLease;
use Modules\Core\Filament\Utils\ReportsApprovalOutcome;
use Override;

final class EditContent extends EditRecord
{
    use HasCloseOrCancelFormAction;
    use HasFilamentFormDataSanitizer;
    use HasRecordLease;
    use ReportsApprovalOutcome;

    #[Override]
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            self::approvalAwareDeleteAction(),
            self::approvalAwareForceDeleteAction(),
            self::approvalAwareRestoreAction(),
        ];
    }
}
