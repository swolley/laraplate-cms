<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\Core\Filament\Utils\HasFilamentFormDataSanitizer;
use Modules\Core\Filament\Utils\HasRecordLease;
use Override;

final class EditContent extends EditRecord
{
    use HasFilamentFormDataSanitizer;
    use HasRecordLease;

    #[Override]
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
