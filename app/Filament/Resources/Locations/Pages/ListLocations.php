<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Locations\LocationResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Override;

final class ListLocations extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = LocationResource::class;
}
