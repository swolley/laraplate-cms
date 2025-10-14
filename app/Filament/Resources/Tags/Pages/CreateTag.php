<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Tags\TagResource;

final class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}
