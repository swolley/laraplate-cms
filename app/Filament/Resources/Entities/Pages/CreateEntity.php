<?php

namespace Modules\Cms\Filament\Resources\Entities\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Entities\EntityResource;

class CreateEntity extends CreateRecord
{
    protected static string $resource = EntityResource::class;
}
