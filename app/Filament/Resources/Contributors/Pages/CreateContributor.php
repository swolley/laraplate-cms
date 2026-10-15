<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contributors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Contributors\ContributorResource;
use Modules\Core\Filament\Utils\CreatesRecordFromFormState;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Override;

final class CreateContributor extends CreateRecord
{
    use CreatesRecordFromFormState;
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = ContributorResource::class;

    /**
     * @return list<string>
     */
    protected function guardedAttributesTheFormEdits(): array
    {
        return ['user_id'];
    }
}
