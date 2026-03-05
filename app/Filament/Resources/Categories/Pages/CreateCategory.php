<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Categories\CategoryResource;
use Override;

final class CreateCategory extends CreateRecord
{
    #[Override]
    protected static string $resource = CategoryResource::class;
}
