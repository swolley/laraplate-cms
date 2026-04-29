<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Categories\CategoryResource;
use Override;

final class CreateCategory extends CreateRecord
{
    #[Override]
    protected static string $resource = CategoryResource::class;
}
