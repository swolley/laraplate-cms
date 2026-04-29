<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Unit\Filament\Utils;

use Modules\CMS\Models\Content;

final class FakeContentFilamentResource
{
    /**
     * @return class-string<Content>
     */
    public static function getModel(): string
    {
        return Content::class;
    }
}
