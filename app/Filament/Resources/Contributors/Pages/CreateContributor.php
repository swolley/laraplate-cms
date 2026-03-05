<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contributors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Contributors\ContributorResource;
use Override;

final class CreateContributor extends CreateRecord
{
    #[Override]
    protected static string $resource = ContributorResource::class;
}
