<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Unit\Filament\Utils;

use Modules\CMS\Filament\Utils\HasRecords;

final class CMSHasRecordsEntityHarness
{
    use HasRecords;

    public static function getResource(): string
    {
        return FakeEntityFilamentResource::class;
    }
}
