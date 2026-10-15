<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contributors\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Contributors\ContributorResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Modules\Core\Filament\Utils\UpdatesRecordFromFormState;
use Override;

final class EditContributor extends EditRecord
{
    use HasCloseOrCancelFormAction;
    use UpdatesRecordFromFormState;

    #[Override]
    protected static string $resource = ContributorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function guardedAttributesTheFormEdits(): array
    {
        return ['user_id'];
    }
}
