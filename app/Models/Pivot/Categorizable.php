<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperCategorizable
 */
final class Categorizable extends Pivot
{
    use HasFactory;

    public $incrementing = false;

    protected $table = 'categorizables';

    protected $primaryKey = ['content_id', 'category_id'];

    protected $keyType = 'array';

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
