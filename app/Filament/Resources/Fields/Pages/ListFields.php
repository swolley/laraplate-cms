<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Fields\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Fields\FieldResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListFields extends ListRecords
{
    use HasRecords;

    protected static string $resource = FieldResource::class;
}
