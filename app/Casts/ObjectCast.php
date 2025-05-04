<?php

declare(strict_types=1);

namespace Modules\Cms\Casts;

use Override;
use stdClass;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;

final class ObjectCast implements CastsInboundAttributes
{
    public function get($model, $key, $value, $attributes): object
    {
        return json_decode((string) $value) ?? new stdClass();
    }

    #[Override]
    public function set($model, $key, $value, $attributes): string
    {
        return json_encode($value);
    }
}
