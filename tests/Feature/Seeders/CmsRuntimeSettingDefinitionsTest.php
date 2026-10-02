<?php

declare(strict_types=1);

use Modules\CMS\Database\Seeders\CMSDatabaseSeeder;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('defines cms runtime settings with current defaults', function (): void {
    $definitions = collect(CMSDatabaseSeeder::runtimeSettingDefinitions())->keyBy('name');

    expect($definitions->get('geocoding.cache_ttl')['value'])->toBe(604800);
});
