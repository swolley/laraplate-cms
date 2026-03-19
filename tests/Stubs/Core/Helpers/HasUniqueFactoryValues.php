<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

trait HasUniqueFactoryValues
{
    protected function uniqueValue(callable|string $value = 'value', mixed ...$unused): string
    {
        if (is_callable($value)) {
            $generated = (string) $value();

            return $generated . '-' . uniqid('', true);
        }

        return $value . '-' . uniqid('', true);
    }
}
