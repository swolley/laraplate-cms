<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Locations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Locations\LocationResource;
use Override;

final class CreateLocation extends CreateRecord
{
    #[Override]
    protected static string $resource = LocationResource::class;
}
