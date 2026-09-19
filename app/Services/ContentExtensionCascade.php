<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Throwable;

/**
 * Shared re-entrancy guard for the content-extension lifecycle (C10).
 *
 * The extender→content cascade (in {@see \Modules\CMS\Models\Concerns\ExtendsContentTrait}) and the
 * content→extender reverse handler (in {@see \Modules\CMS\Observers\ContentExtensionObserver}) both
 * check this flag so that a deletion travelling one direction does not re-trigger the other and loop.
 */
final class ContentExtensionCascade
{
    public static bool $active = false;

    /**
     * Run a cascade step with the guard raised; skip entirely if a cascade is already in progress.
     */
    public static function guard(callable $callback): void
    {
        if (self::$active) {
            return;
        }

        self::$active = true;

        try {
            $callback();
        } catch (Throwable $e) {
            self::$active = false;

            throw $e;
        }

        self::$active = false;
    }
}
