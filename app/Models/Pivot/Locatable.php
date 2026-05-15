<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Modules\CMS\Enums\CMSTables;
use Modules\Core\Overrides\Pivot;
use Override;

/**
 * @mixin \Eloquent
 * @mixin IdeHelperLocatable
 */
final class Locatable extends Pivot
{
    #[Override]
    protected $table = CMSTables::Locatables->value;

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
