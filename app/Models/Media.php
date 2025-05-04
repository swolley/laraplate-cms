<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Support\Carbon;
use Modules\Core\Helpers\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * @mixin IdeHelperMedia
 */
final class Media extends BaseMedia
{
    use SoftDeletes;

    protected $appends = [
        'expires_at',
    ];

    public function getExpiresAtAttribute(): ?Carbon
    {
        $expirationDays = config('core.soft_deletes_expiration_days');

        return $this->trashed() && $expirationDays ? $this->deleted_at->addDays($expirationDays) : null;
    }
}
