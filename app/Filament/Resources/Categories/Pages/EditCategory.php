<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Categories\CategoryResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\Core\Filament\Utils\HasRecordLease;
use Modules\Core\Filament\Utils\ReportsApprovalOutcome;
use Override;

final class EditCategory extends EditRecord
{
    use HasCloseOrCancelFormAction;
    use HasRecordLease;
    use ReportsApprovalOutcome;

    #[Override]
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            self::approvalAwareDeleteAction(),
            self::approvalAwareForceDeleteAction(),
            self::approvalAwareRestoreAction(),
        ];
    }
}
