<?php

declare(strict_types=1);

namespace Modules\CMS\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class CMSPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'CMS';
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
