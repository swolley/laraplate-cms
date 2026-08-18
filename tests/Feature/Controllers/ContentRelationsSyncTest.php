<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class, RefreshDatabase::class);

function relationsSyncSuperadmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(config('permission.roles.superadmin'), 'web'));

    return $user;
}

function makeSyncableContent(): Content
{
    return Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
}

/**
 * The attached related ids, ignoring the related models' locale/soft-delete
 * scopes — the endpoint syncs pivot rows, not locale-visible rows.
 *
 * @return list<int>
 */
function relatedIds(Content $content, string $relation): array
{
    return $content->{$relation}()
        ->withoutGlobalScopes()
        ->get()
        ->pluck('id')
        ->sort()
        ->values()
        ->all();
}

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Categories, EntityType::Contributors]);
});

it('syncs every relation of a content by id', function (): void {
    $content = makeSyncableContent();
    $categories = Category::factory()->count(2)->create();
    $contributor = Contributor::factory()->create();
    $location = Location::factory()->create();
    $tag = Tag::factory()->create();

    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        [
            'categories' => $categories->pluck('id')->all(),
            'contributors' => [$contributor->id],
            'locations' => [$location->id],
            'tags' => [$tag->id],
        ],
    );

    $response->assertOk();

    $content->refresh();
    expect(relatedIds($content, 'categories'))->toBe($categories->pluck('id')->sort()->values()->all())
        ->and(relatedIds($content, 'contributors'))->toBe([$contributor->id])
        ->and(relatedIds($content, 'locations'))->toBe([$location->id])
        ->and(relatedIds($content, 'tags'))->toBe([$tag->id]);
});

it('replaces an existing relation set rather than appending', function (): void {
    $content = makeSyncableContent();
    $old = Category::factory()->create();
    $new = Category::factory()->create();
    $content->categories()->sync([$old->id]);

    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        ['categories' => [$new->id]],
    );

    $response->assertOk();

    expect($content->categories()->get()->pluck('id')->all())->toBe([$new->id]);
});

it('leaves an omitted relation untouched', function (): void {
    $content = makeSyncableContent();
    $category = Category::factory()->create();
    $contributor = Contributor::factory()->create();
    $content->categories()->sync([$category->id]);

    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        ['contributors' => [$contributor->id]],
    );

    $response->assertOk();

    expect(relatedIds($content, 'categories'))->toBe([$category->id])
        ->and(relatedIds($content, 'contributors'))->toBe([$contributor->id]);
});

it('clears a relation when an empty array is sent', function (): void {
    $content = makeSyncableContent();
    $category = Category::factory()->create();
    $content->categories()->sync([$category->id]);

    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        ['categories' => []],
    );

    $response->assertOk();

    expect($content->categories()->count())->toBe(0);
});

it('denies syncing without the update permission on contents', function (): void {
    $content = makeSyncableContent();
    $category = Category::factory()->create();

    $response = $this->actingAs(User::factory()->create())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        ['categories' => [$category->id]],
    );

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    expect($content->categories()->count())->toBe(0);
});

it('returns 404 for an unknown content', function (): void {
    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => 999999]),
        ['categories' => []],
    );

    $response->assertNotFound();
});

it('rejects non-integer relation ids', function (): void {
    $content = makeSyncableContent();

    $response = $this->actingAs(relationsSyncSuperadmin())->postJson(
        route('cms.contents.relations', ['content' => $content->id]),
        ['categories' => ['not-an-id']],
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});
