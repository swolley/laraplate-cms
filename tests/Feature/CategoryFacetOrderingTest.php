<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Database\Seeders\CMSDatabaseSeeder;
use Modules\CMS\Models\Category;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

it('does not leak the default ordering scope into facet aggregate queries', function (): void {
    $this->artisan('permission:refresh');
    $this->seed(CMSDatabaseSeeder::class);
    $parent = Category::factory()->create();
    Category::factory()->count(2)->create(['parent_id' => $parent->id]);

    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findOrCreate('superadmin', 'web'));

    DB::enableQueryLog();

    $this->actingAs($viewer)->postJson(route('core.crud.facets', ['module' => 'cms', 'entity' => 'categories']), [
        'facet' => ['groupBy' => 'parent_id', 'fields' => ['parent.name'], 'sort' => 'count_desc'],
    ])->assertOk();

    $aggregates = collect(DB::getQueryLog())->pluck('query')->filter(
        static fn (string $sql): bool => str_contains($sql, 'group by') && str_contains($sql, 'count(*)'),
    );

    expect($aggregates)->not->toBeEmpty()
        ->and($aggregates->filter(static fn (string $sql): bool => str_contains($sql, 'order_column')))->toBeEmpty();
});

it('orders a relation facet by an aggregated label so only_full_group_by holds', function (): void {
    $this->artisan('permission:refresh');
    $this->seed(CMSDatabaseSeeder::class);
    Category::factory()->count(2)->create();

    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findOrCreate('superadmin', 'web'));

    DB::enableQueryLog();

    $this->actingAs($viewer)->postJson(route('core.crud.facets', ['module' => 'cms', 'entity' => 'contents']), [
        'facet' => ['groupBy' => 'categories', 'relation' => 'categories', 'fields' => ['translations.name'], 'labelField' => 'translations.name', 'sort' => 'label_asc'],
    ])->assertOk();

    $grouped = collect(DB::getQueryLog())->pluck('query')->filter(
        static fn (string $sql): bool => str_contains($sql, 'cms_categorizables') && str_contains($sql, 'group by') && str_contains($sql, 'order by min('),
    );

    expect($grouped)->not->toBeEmpty();
});
