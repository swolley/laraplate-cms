<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @mixin IdeHelperCategorizable
 */
final class Categorizable extends Pivot
{
    protected $table = 'categorizables';

    #[Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
