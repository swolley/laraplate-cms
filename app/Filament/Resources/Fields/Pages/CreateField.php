<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Fields\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Cms\Filament\Resources\Fields\FieldResource;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;
}
