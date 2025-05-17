<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Core\Helpers\HasVersions;
use Override;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @mixin IdeHelperFieldable
 */
final class Fieldable extends Pivot implements Sortable
{
    use HasVersions, SortableTrait;

    public $incrementing = true;

    protected $table = 'fieldables';

    protected $attributes = [
        'is_required' => false,
        'order_column' => 0,
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'field_id',
        'preset_id',
        'order_column',
    ];

    protected $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    public function ordered(Builder $query): Builder
    {
        return $query->orderBy('order_column', 'asc');
    }

    #[Override]
    protected function casts()
    {
        return [
            'is_required' => 'boolean',
            'order_column' => 'integer',
            'default' => 'json',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
