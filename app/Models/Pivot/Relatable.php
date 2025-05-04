<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Override;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperRelatable
 */
final class Relatable extends Pivot
{
    protected $table = 'relatables';

    #[Override]
    protected function casts()
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
