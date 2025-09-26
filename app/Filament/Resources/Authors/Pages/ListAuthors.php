<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Authors\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Authors\AuthorResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListAuthors extends ListRecords
{
    use HasRecords;

    protected static string $resource = AuthorResource::class;
}
