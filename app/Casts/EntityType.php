<?php

declare(strict_types=1);

namespace Modules\Cms\Casts;

enum EntityType: string
{
    case CONTENTS = 'contents';
    case AUTHORS = 'authors';
    case CATEGORIES = 'categories';

    /**
     * Get all values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if value is valid.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Get validation rules for Laravel.
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::values());
    }
}
