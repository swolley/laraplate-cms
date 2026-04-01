<?php

declare(strict_types=1);

namespace Modules\Core\Cache;

trait HasCache
{
    public function getCacheKey(): string
    {
        return static::class;
    }
}
