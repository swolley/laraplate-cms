<?php

declare(strict_types=1);

use Filament\Panel;
use Modules\CMS\Filament\CMSPlugin;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('exposes cms plugin metadata and boots without error', function (): void {
    $plugin = new CMSPlugin();

    expect($plugin->getId())->toBe('cms')
        ->and($plugin->getModuleName())->toBe('CMS');

    $plugin->boot(Panel::make('cms-test'));
});
