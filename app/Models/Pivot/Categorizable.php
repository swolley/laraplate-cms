<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Override;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperCategorizable
 */
final class Categorizable extends Pivot
{
    protected $table = 'categorizables';

    #[Override]
    protected function casts()
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
