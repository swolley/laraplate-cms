<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Unit\Filament\Utils;

use Modules\Cms\Filament\Utils\HasRecords;

final class CmsHasRecordsTraitHarness
{
    use HasRecords;

    /**
     * @return class-string
     */
    public static function getResource(): string
    {
        return FakeContentFilamentResource::class;
    }
}
