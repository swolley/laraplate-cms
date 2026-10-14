<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Casts\ScopeFilter;
use Modules\Core\Models\ACL;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Support\CrudApiExposure;
use Modules\Core\Support\PermissionName;

uses(TestCase::class, RefreshDatabase::class);

/*
 * The facets of a caller narrowed by an ACL stay within the rows the ACL lets it see, whatever the request
 * filters: a request whose root group is OR must not let its branches escape the ACL, in the counts, in the
 * values of a column facet or in the values of a relation facet.
 */

beforeEach(function (): void {
    Cache::flush();
    Carbon::setTestNow('2026-08-19 12:00:00');
    app()->setLocale('en');
    CrudApiExposure::enable();
    setupCMSEntities([EntityType::Contents, EntityType::Categories]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * A live content and two the ACL hides (scheduled, expired); the live one in a category of its own, the hidden
 * ones in another.
 *
 * @return array{live: Content, scheduled: Content, expired: Content, visible_category: Category, hidden_category: Category}
 */
function facetacl_records(): array
{
    $live = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $scheduled = Content::factory()->create(['valid_from' => now()->addWeek(), 'valid_to' => null]);
    $expired = Content::factory()->create(['valid_from' => now()->subMonth(), 'valid_to' => now()->subDay()]);

    $category_state = ['is_active' => true, 'valid_from' => now()->subYear(), 'valid_to' => null];
    $visible_category = Category::factory()->create($category_state);
    $hidden_category = Category::factory()->create($category_state);
    $live->categories()->sync([$visible_category->id]);
    $scheduled->categories()->sync([$hidden_category->id]);
    $expired->categories()->sync([$hidden_category->id]);

    return ['live' => $live, 'scheduled' => $scheduled, 'expired' => $expired, 'visible_category' => $visible_category, 'hidden_category' => $hidden_category];
}

/**
 * An `api` reader of contents narrowed by the ACL `valid`, who may read every category.
 */
function facetacl_reader(): App\Models\User
{
    $role = Role::factory()->create(['name' => 'facetacl_reader_' . uniqid(), 'guard_name' => 'api']);

    foreach ([Content::class, Category::class] as $model_class) {
        $name = PermissionName::forClass($model_class, 'select');
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $permission = Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        $role->givePermissionTo($permission);

        if ($model_class === Content::class) {
            $acl = new ACL;
            $acl->forceFill([
                'permission_id' => $permission->id,
                'role_id' => $role->id,
                'filters' => new FiltersGroup([new ScopeFilter('valid')]),
                'unrestricted' => false,
                'priority' => 10,
                'is_active' => true,
            ]);
            $acl->save();
        }
    }

    $user = App\Models\User::query()->findOrFail(User::factory()->create()->getKey());
    $user->assignRole($role);

    return $user->fresh();
}

/**
 * A request root OR group every content matches (valid from before or after now), on a column no facet here groups by.
 *
 * @return array<string, mixed>
 */
function facetacl_root_or(): array
{
    return ['operator' => 'or', 'filters' => [
        ['property' => 'valid_from', 'operator' => 'le', 'value' => '2026-08-19 12:00:00'],
        ['property' => 'valid_from', 'operator' => 'gt', 'value' => '2026-08-19 12:00:00'],
    ]];
}

/**
 * @param  array<string, mixed>  $query
 */
function facetacl_get(App\Models\User $reader, array $query): TestResponse
{
    return test()->actingAs($reader)->getJson('/api/v1/facets/cms/contents?' . http_build_query($query));
}

/**
 * @return list<int>
 */
function facetacl_keys(TestResponse $response): array
{
    $keys = array_map(static fn (mixed $key): int => (int) $key, collect($response->json('data.values'))->pluck('key')->all());
    sort($keys);

    return $keys;
}

it('never counts a row the ACL hides when the request root group is OR', function (): void {
    $records = facetacl_records();

    $response = facetacl_get(facetacl_reader(), [
        'columns' => ['entity_id'],
        'filters' => json_encode(facetacl_root_or(), JSON_THROW_ON_ERROR),
    ]);

    $response->assertOk();
    expect($response->json('data.entity_id'))->toBe([['value' => $records['live']->entity_id, 'total' => 1, 'count' => 1]]);
});

it('never returns the key of a row the ACL hides in a column facet when the request root group is OR', function (): void {
    $records = facetacl_records();

    $response = facetacl_get(facetacl_reader(), [
        'facet' => ['groupBy' => 'id'],
        'filters' => json_encode(facetacl_root_or(), JSON_THROW_ON_ERROR),
    ]);

    $response->assertOk();
    expect(facetacl_keys($response))->toBe([$records['live']->id]);
});

it('never returns a related key reached only through a row the ACL hides when the request root group is OR', function (): void {
    $records = facetacl_records();

    $response = facetacl_get(facetacl_reader(), [
        'facet' => ['groupBy' => 'categories', 'relation' => 'categories'],
        'filters' => json_encode(facetacl_root_or(), JSON_THROW_ON_ERROR),
    ]);

    $response->assertOk();
    expect(facetacl_keys($response))->toBe([$records['visible_category']->id]);
});
