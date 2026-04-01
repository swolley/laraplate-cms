<?php

declare(strict_types=1);

if (! function_exists('user_class')) {
    /**
     * @return class-string<Illuminate\Contracts\Auth\Authenticatable&Illuminate\Database\Eloquent\Model>
     */
    function user_class(): string
    {
        return Modules\Cms\Tests\Support\User::class;
    }
}

if (! function_exists('class_uses_trait')) {
    /**
     * Match Core behaviour: traits used by nested traits must count (e.g. HasTranslatedDynamicContents → HasDynamicContents).
     *
     * @param  class-string|object  $class
     */
    function class_uses_trait(object|string $class, string $trait, bool $recursive = true): bool
    {
        $traits = $recursive ? class_uses_recursive($class) : class_uses($class);

        if ($traits === false || $traits === []) {
            return false;
        }

        return in_array($trait, $traits, true);
    }
}
