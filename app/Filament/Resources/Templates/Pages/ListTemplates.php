<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Templates\TemplateResource;
use Modules\Cms\Filament\Utils\HasRecords;

class ListTemplates extends ListRecords
{
    use HasRecords;

    protected static string $resource = TemplateResource::class;
}
