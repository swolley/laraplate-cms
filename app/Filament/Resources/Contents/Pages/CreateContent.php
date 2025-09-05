<?php

namespace Modules\Cms\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Contents\ContentResource;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;
}
