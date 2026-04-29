<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Entities\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Entities\EntityResource;
use Override;

final class CreateEntity extends CreateRecord
{
    #[Override]
    protected static string $resource = EntityResource::class;
}
