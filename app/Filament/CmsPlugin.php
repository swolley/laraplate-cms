<?php

declare(strict_types=1);

namespace Modules\Cms\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class CmsPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Cms';
    }

    public function getId(): string
    {
        return 'cms';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
