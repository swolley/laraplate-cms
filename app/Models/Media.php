<?php

namespace Modules\Cms\Models;

use Illuminate\Support\Carbon;
use Modules\Core\Helpers\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * @mixin IdeHelperMedia
 */
class Media extends BaseMedia
{
    use SoftDeletes;

    protected $appends = [
        'expires_at',
    ];

    protected function casts(): array
    {
        return $this->casts + [
            'deleted_at' => 'datetime',
        ];
    }

    public function getExpiresAtAttribute(): ?Carbon
    {
        $expirationDays = config('core.soft_deletes_expiration_days');

        return $this->isTrashed() && $expirationDays ? $this->deleted_at->addDays($expirationDays) : null;
    }
}
