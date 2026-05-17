<?php

declare(strict_types=1);

namespace Modules\CMS\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Override;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    #[Override]
    protected $listen = [];

    #[Override]
    protected static $shouldDiscoverEvents = true;
}
