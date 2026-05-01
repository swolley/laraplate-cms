<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Unit\Filament\Utils;

use Modules\CMS\Filament\Utils\HasRecords;

final class CMSHasRecordsTraitHarness extends CMSFilamentHarnessBase
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
