<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Templates\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\CMS\Filament\Resources\Templates\TemplateResource;
use Modules\CMS\Filament\Utils\HasRecords;
use Override;

final class ListTemplates extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = TemplateResource::class;
}
