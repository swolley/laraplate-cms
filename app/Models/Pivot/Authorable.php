<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperAuthorable
 */
final class Authorable extends Pivot
{
    use HasFactory;

    protected $table = 'authorables';

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
