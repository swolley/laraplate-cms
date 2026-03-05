<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Contents\ContentResource;
use Override;

final class CreateContent extends CreateRecord
{
    #[Override]
    protected static string $resource = ContentResource::class;
}
