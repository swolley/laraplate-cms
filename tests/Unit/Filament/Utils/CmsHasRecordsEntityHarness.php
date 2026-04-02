<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Unit\Filament\Utils;

use Modules\Cms\Filament\Utils\HasRecords;

final class CmsHasRecordsEntityHarness
{
    use HasRecords;

    public static function getResource(): string
    {
        return FakeEntityFilamentResource::class;
    }
}
