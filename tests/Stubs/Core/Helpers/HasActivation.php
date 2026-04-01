<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stub aligned with Core: scoped as {@see active()} via {@see Scope} (not legacy {@see scopeActive} name).
 */
trait HasActivation
{
    protected static string $activation_column = 'is_active';

    public static function activationColumn(): string
    {
        return static::$activation_column;
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            static::$activation_column => 'boolean',
        ];
    }
}
