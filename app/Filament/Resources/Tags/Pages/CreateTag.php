<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CMS\Filament\Resources\Tags\TagResource;
use Override;

final class CreateTag extends CreateRecord
{
    #[Override]
    protected static string $resource = TagResource::class;
}
