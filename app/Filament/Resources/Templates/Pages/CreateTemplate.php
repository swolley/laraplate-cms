<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Templates\TemplateResource;
use Override;

final class CreateTemplate extends CreateRecord
{
    #[Override]
    protected static string $resource = TemplateResource::class;
}
