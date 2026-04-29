<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Override;

final class CreateContent extends CreateRecord
{
    #[Override]
    protected static string $resource = ContentResource::class;
}
