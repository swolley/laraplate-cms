<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Unit\Filament\Utils;

use Modules\Cms\Models\Content;

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
