<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Preset;
use Modules\Core\Helpers\SoftDeletes;

/**
 * @mixin IdeHelperPresettable
 */
final class Presettable extends Pivot
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'presettables';

    protected $with = [
        'preset',
        'entity',
    ];

    public function preset(): BelongsTo
    {
        return $this->belongsTo(Preset::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    protected function casts(): array
    {
        return [
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
