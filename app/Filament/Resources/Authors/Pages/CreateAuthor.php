<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Authors\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Authors\AuthorResource;

class CreateAuthor extends CreateRecord
{
    protected static string $resource = AuthorResource::class;
}
