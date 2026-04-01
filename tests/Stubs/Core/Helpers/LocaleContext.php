<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

final class LocaleContext
{
    public static function get(): string
    {
        return (string) config('app.locale', 'en');
    }

    public static function isFallbackEnabled(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    public static function getAvailable(): array
    {
        return [config('app.locale', 'en')];
    }
}
