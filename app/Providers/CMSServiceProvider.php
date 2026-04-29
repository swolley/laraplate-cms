<?php

declare(strict_types=1);

namespace Modules\CMS\Providers;

use Exception;
use Modules\Core\Overrides\ModuleServiceProvider;
use Override;

/**
 * @property \Illuminate\Foundation\Application $app
 */
final class CMSServiceProvider extends ModuleServiceProvider
{
    #[Override]
    protected string $name = 'CMS';

    #[Override]
    protected string $nameLower = 'cms';

    /**
     * Register the service provider.
     *
     * @throws Exception
     */
    #[Override]
    public function register(): void
    {
        parent::register();
    }
}
