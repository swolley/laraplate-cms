<?php

declare(strict_types=1);

namespace Modules\CMS\Services\Contracts;

trait GeocodingServiceSingleton
{
    /**
     * @var array<class-string<static>, static>
     */
    private static array $geocodingServiceInstances = [];

    public static function getInstance(): static
    {
        return self::$geocodingServiceInstances[static::class] ??= new static();
    }
}
