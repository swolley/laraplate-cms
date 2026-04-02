<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Minimal contract stub for CMS submodule tests when Core is not on the autoloader.
 */
interface IDynamicEntityTypable
{
    /**
     * @return array<string>
     */
    public static function values(): array;

    public static function isValid(string $value): bool;

    public static function validationRule(): string;

    public static function tryFrom(string $value): ?static;

    public function toScalar(): string;
}
