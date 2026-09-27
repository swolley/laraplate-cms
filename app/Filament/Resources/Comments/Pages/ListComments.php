<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Comments\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\CMS\Filament\Resources\Comments\CommentResource;
use Modules\CMS\Filament\Utils\HasRecords;
use Override;

final class ListComments extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = CommentResource::class;
}
