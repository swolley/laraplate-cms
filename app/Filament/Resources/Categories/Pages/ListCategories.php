<?php

namespace Modules\Cms\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Categories\CategoryResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListCategories extends ListRecords
{
    use HasRecords;

    protected static string $resource = CategoryResource::class;
}
