<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Tags\TagResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListTags extends ListRecords
{
    use HasRecords;

    protected static string $resource = TagResource::class;
}
