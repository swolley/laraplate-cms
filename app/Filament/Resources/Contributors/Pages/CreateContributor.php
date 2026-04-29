<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contributors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Contributors\ContributorResource;
use Override;

final class CreateContributor extends CreateRecord
{
    #[Override]
    protected static string $resource = ContributorResource::class;
}
