<?php

namespace Modules\Cms\Filament\Resources\Locations\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Locations\LocationResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListLocations extends ListRecords
{
    use HasRecords;

    protected static string $resource = LocationResource::class;
}
