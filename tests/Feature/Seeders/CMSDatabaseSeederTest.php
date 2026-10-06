<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Database\Seeders\CMSDatabaseSeeder;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\ACL;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\Setting;

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
        ->where('name', 'geocoding.cache_ttl')
        ->update(['value' => json_encode(1), 'description' => 'drifted']);

    $this->seed(CMSDatabaseSeeder::class);

    $setting = Setting::query()->withoutGlobalScopes()
        ->where('name', 'geocoding.cache_ttl')->sole();

    expect($setting->value)->toBe(1)
        ->and($setting->description)->toBe('Geocoding cache TTL in seconds');
});

it('gives the publisher the shared taxonomy permissions, narrowed to CMS rows by an ACL', function (): void {
    $this->artisan('permission:refresh')->assertSuccessful();
    $this->seed(CMSDatabaseSeeder::class);

    $publisher = Role::query()->where('name', 'publisher')->firstOrFail();
    $permission = Permission::query()->where('name', 'default.core_taxonomies.select')->firstOrFail();

    expect($publisher->permissions->pluck('name'))->toContain('default.core_taxonomies.select', 'default.cms_contents.select');

    $acl = ACL::query()->where('permission_id', $permission->id)->where('role_id', $publisher->id)->sole();

    expect($acl->filters->filters[0]->property)->toBe('presettable.entity.type');

    $this->seed(CMSDatabaseSeeder::class);

    expect(ACL::query()->where('permission_id', $permission->id)->where('role_id', $publisher->id)->count())->toBe(1);
});
