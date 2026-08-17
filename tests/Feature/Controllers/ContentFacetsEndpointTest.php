<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;

uses(TestCase::class, RefreshDatabase::class);

function facets_superadmin(): User
{
    $role = Role::factory()->create(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('returns a relation facet with translated labels over the endpoint', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents, EntityType::Categories]);

    $cinema = Category::factory()->create();
    $cinema->translations()->where('locale', 'en')->update(['name' => 'Cinema']);

    $one = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $one->categories()->sync([$cinema->id]);
    $two = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $two->categories()->sync([$cinema->id]);

    $response = $this->actingAs(facets_superadmin())->getJson(
        route('core.crud.facets', ['module' => 'cms', 'entity' => 'contents'])
            . '?' . http_build_query([
                'facet' => [
                    'groupBy' => 'categories',
                    'relation' => 'categories',
                    'fields' => ['translations.name'],
                    'labelField' => 'translations.name',
                    'sort' => 'count_desc',
                ],
            ]),
    );

    $response->assertOk();

    $value = collect($response->json('data.values'))->firstWhere('key', $cinema->id);

    expect($value['count'])->toBe(2)
        ->and($value['total'])->toBe(2)
        ->and($value['attributes']['translations.name'])->toBe('Cinema');
});
