<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Modules\CMS\Enums\CMSTables;
use Modules\Core\Overrides\Pivot;
use Override;

/**
 * @mixin \Eloquent
 * @mixin IdeHelperContributable
 */
final class Contributable extends Pivot
{
    #[Override]
    protected $table = CMSTables::Contributables->value;

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
