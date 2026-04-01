<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Builder;

/**
 * Mirrors {@see SortableTrait} for standalone CMS tests.
 * Must expose a public scopeOrdered() to satisfy {@see \Spatie\EloquentSortable\Sortable}.
 */
trait SortableTrait
{
    use \Spatie\EloquentSortable\SortableTrait {
        scopeOrdered as private scopeOrderedTrait;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy($this->qualifyColumn($this->determineOrderColumnName()), $direction);
    }
}
