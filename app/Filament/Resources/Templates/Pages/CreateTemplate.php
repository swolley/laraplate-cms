<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Templates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Templates\TemplateResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Override;

final class CreateTemplate extends CreateRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = TemplateResource::class;
}
