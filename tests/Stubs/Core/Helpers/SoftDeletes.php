<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\SoftDeletes as LaravelSoftDeletes;

trait SoftDeletes
{
    use LaravelSoftDeletes;
}
