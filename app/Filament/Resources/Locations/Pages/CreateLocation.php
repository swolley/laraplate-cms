<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Locations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Locations\LocationResource;
use Override;

final class CreateLocation extends CreateRecord
{
    #[Override]
    protected static string $resource = LocationResource::class;
}
