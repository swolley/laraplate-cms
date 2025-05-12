<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @mixin IdeHelperAuthorable
 */
final class Authorable extends Pivot
{
    protected $table = 'authorables';

    #[Override]
    protected function casts()
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
