<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Entities\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Entities\EntityResource;

final class CreateEntity extends CreateRecord
{
    protected static string $resource = EntityResource::class;
}
