<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Templates\TemplateResource;

final class CreateTemplate extends CreateRecord
{
    protected static string $resource = TemplateResource::class;
}
