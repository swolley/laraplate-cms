<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @mixin IdeHelperContributable
 */
final class Contributable extends Pivot
{
    use HasFactory;

    #[Override]
    protected $table = 'contributables';

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
