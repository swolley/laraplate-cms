<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contributors\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Contributors\ContributorResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Override;

final class ListContributors extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = ContributorResource::class;
}
