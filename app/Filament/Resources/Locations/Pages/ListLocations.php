<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Locations\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\CMS\Filament\Resources\Locations\LocationResource;
use Modules\CMS\Filament\Utils\HasRecords;
use Override;

final class ListLocations extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = LocationResource::class;
}
