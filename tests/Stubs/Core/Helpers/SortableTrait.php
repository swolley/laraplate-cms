<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Builder;

trait SortableTrait
{
    public static function setNewOrder($ids, int $startOrder = 1): void {}

    public function scopeOrdered(Builder $query): void {}

    public function setHighestOrderNumber(): void {}

    public function shouldSortWhenCreating(): bool
    {
        return false;
    }
}
