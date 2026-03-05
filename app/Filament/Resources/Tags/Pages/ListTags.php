<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Cms\Filament\Resources\Tags\TagResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Override;

final class ListTags extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = TagResource::class;
}
