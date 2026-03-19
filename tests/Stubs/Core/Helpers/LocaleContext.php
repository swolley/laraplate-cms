<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

final class LocaleContext
{
    /**
     * @return list<string>
     */
    public static function getAvailable(): array
    {
        return [config('app.locale', 'en')];
    }
}
