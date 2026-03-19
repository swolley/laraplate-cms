<?php

declare(strict_types=1);

if (! function_exists('class_uses_trait')) {
    function class_uses_trait(object|string $class, string $trait): bool
    {
        $traits = class_uses($class);

        if ($traits === false) {
            return false;
        }

        return in_array($trait, $traits, true);
    }
}
