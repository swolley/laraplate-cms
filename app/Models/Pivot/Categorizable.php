<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Modules\CMS\Enums\CMSTables;
use Modules\Core\Overrides\Pivot;
use Override;

/**
 * @mixin \Eloquent
 * @mixin IdeHelperCategorizable
 */
final class Categorizable extends Pivot
{
    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = CMSTables::Categorizables->value;

    #[Override]
    protected $primaryKey = ['content_id', 'taxonomy_id'];

    #[Override]
    protected $keyType = 'array';

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
