<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\Core\Helpers\SoftDeletes;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * @mixin IdeHelperMedia
 */
final class Media extends BaseMedia
{
    use HasFactory;
    use SoftDeletes;

    #[Override]
    protected $appends = [
        'expires_at',
    ];

    protected function getExpiresAtAttribute(): ?Carbon
    {
        $expirationDays = config('core.soft_deletes_expiration_days');

        return $this->trashed() && $expirationDays ? $this->{self::getDeletedAtColumn()}->addDays($expirationDays) : null;
    }
}
