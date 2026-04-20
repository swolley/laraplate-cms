<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

/**
 * @mixin IdeHelperCategorizable
 */
final class Categorizable extends Pivot
{
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'categorizables';

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
