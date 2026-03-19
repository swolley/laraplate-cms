<?php

declare(strict_types=1);

namespace Modules\Core\Overrides;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    protected string $name = '';

    protected string $nameLower = '';
}
