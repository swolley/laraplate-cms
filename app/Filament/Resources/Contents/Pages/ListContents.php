<?php

namespace Modules\Cms\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Contents\ContentResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListContents extends ListRecords
{
    use HasRecords;

    protected static string $resource = ContentResource::class;
}
