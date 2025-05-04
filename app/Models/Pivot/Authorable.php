<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Override;
use Illuminate\Database\Eloquent\Relations\Pivot;

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
