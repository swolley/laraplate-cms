<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Builder;

trait HasValidity
{
    public function scopeValid(Builder $query): void {}
}
