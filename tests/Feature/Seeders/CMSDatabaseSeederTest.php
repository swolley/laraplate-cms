<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Database\Seeders\CMSDatabaseSeeder;
use Modules\Core\Models\Setting;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('seeds CMS runtime settings stamped with the CMS module', function (): void {
    $this->seed(CMSDatabaseSeeder::class);

    $names = collect(CMSDatabaseSeeder::runtimeSettingDefinitions())->pluck('name');

    $settings = Setting::query()->withoutGlobalScopes()->whereIn('name', $names)->get();

    expect($settings)->toHaveCount($names->count())
        ->and($settings->pluck('module')->unique()->all())->toBe(['CMS']);
});

it('is idempotent and leaves an operator-changed value untouched on a second run', function (): void {
    $this->seed(CMSDatabaseSeeder::class);

    Setting::query()->withoutGlobalScopes()
        ->where('name', 'cms.geocoding.cache_ttl')
        ->update(['value' => json_encode(1), 'description' => 'drifted']);

    $this->seed(CMSDatabaseSeeder::class);

    $setting = Setting::query()->withoutGlobalScopes()
        ->where('name', 'cms.geocoding.cache_ttl')->sole();

    expect($setting->value)->toBe(1)
        ->and($setting->description)->toBe('Geocoding cache TTL in seconds');
});
