<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contributors\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\CMS\Filament\Resources\Contributors\ContributorResource;
use Modules\CMS\Filament\Utils\HasRecords;
use Override;

final class ListContributors extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = ContributorResource::class;
}
