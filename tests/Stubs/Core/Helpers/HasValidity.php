<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Minimal stub: registers {@see valid()} scope used by CMS models in global scopes.
 */
trait HasValidity
{
    #[Scope]
    protected function valid(Builder $query): Builder
    {
        return $query;
    }
}
