<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $content = new Content;

    if (! $content->getConnection()->getSchemaBuilder()->hasColumn($content->getTable(), 'shared_components')) {
        $this->markTestSkipped('Contents controller integration requires full Core runtime.');
    }

    setupCMSEntities();

    $role = Role::factory()->create(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    $this->user = user_class()::factory()->create();
    $this->user->assignRole($role);
    $this->actingAs($this->user);

    $this->category = Category::factory()->create();
    $this->category->name = 'Test Category';
    $this->category->slug = 'test-category';
    $this->category->save();

    $this->contributor = Contributor::factory()->create();
    $this->contributor->name = 'Test Contributor';
    $this->contributor->slug = 'test-contributor';
    $this->contributor->save();

    $this->content = Content::factory()->create();
    $this->content->title = 'Test Content';
    $this->content->save();
    $this->content->categories()->attach($this->category->id);
    $this->content->contributors()->attach($this->contributor->id);
});

test('get contents by relation returns contents for category', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'title', 'slug',
                ],
            ],
        ]);
});

test('get contents by relation filters by category', function (): void {
    $otherCategory = Category::factory()->create();
    $otherCategory->name = 'Other Category';
    $otherCategory->slug = 'other-category';
    $otherCategory->save();

    $otherContent = Content::factory()->create();
    $otherContent->title = 'Other Content';
    $otherContent->save();
    $otherContent->categories()->attach($otherCategory->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation filters by contributor', function (): void {
    $otherContributor = Contributor::factory()->create();
    $otherContributor->name = 'Other Contributor';
    $otherContributor->slug = 'other-contributor';
    $otherContributor->save();

    $otherContent = Content::factory()->create();
    $otherContent->title = 'Other Content';
    $otherContent->save();
    $otherContent->contributors()->attach($otherContributor->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'contributors',
        'value' => 'Test Contributor',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation filters by location', function (): void {
    $location = Location::factory()->create(['name' => 'Test Location', 'country' => 'IT']);
    $this->content->locations()->attach($location->id);

    $otherLocation = Location::factory()->create(['name' => 'Other Location', 'country' => 'IT']);
    $otherContent = Content::factory()->create();
    $otherContent->locations()->attach($otherLocation->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'locations',
        'value' => 'Test Location',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation filters by tag', function (): void {
    $this->content->attachTags(['Test Tag']);

    $otherContent = Content::factory()->create();
    $otherContent->attachTags(['Other Tag']);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'tags',
        'value' => 'Test Tag',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation filters by slug', function (): void {
    $otherCategory = Category::factory()->create();
    $otherCategory->name = 'Other Category';
    $otherCategory->slug = 'other-category';
    $otherCategory->save();

    $otherContent = Content::factory()->create();
    $otherContent->categories()->attach($otherCategory->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'test-category',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation handles singular entity names', function (): void {
    $response = $this->getJson('/api/v1/select/cms/categories/Test%20Category/content');

    $response->assertStatus(404);
});

test('get contents by relation returns empty array when no contents', function (): void {
    $emptyCategory = Category::factory()->create();
    $emptyCategory->name = 'Empty Category';
    $emptyCategory->slug = 'empty-category';
    $emptyCategory->save();

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Empty Category',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
        ]);
});

test('get contents by relation handles invalid relation', function (): void {
    $response = $this->getJson('/api/v1/select/cms/invalid/1/contents');

    $response->assertStatus(404);
});

test('get contents by relation handles invalid value', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => '99999',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
        ]);
});

test('get contents by relation supports pagination', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $extra = Content::factory()->create();
        $extra->categories()->attach($this->category->id);
    }

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
        'page' => 1,
        'pagination' => 3,
        // Opt into the counted mode: the paginated list default is now look-ahead,
        // which omits totalRecords/totalPages in favour of hasMore.
        'totals' => true,
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [],
            'meta' => [
                'currentPage',
                'pagination',
                'totalRecords',
                'totalPages',
            ],
        ]);

    expect($response->json('meta.totalRecords'))->toBe(6);
    expect($response->json('meta.totalPages'))->toBe(2);
    expect($response->json('meta.pagination'))->toBe(3);
});

test('get contents by relation supports sorting', function (): void {
    $a = Content::factory()->create();
    $a->title = 'A Content';
    $a->save();
    $a->categories()->attach($this->category->id);

    $z = Content::factory()->create();
    $z->title = 'Z Content';
    $z->save();
    $z->categories()->attach($this->category->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
        'page' => 1,
        'pagination' => 25,
        'sort' => [
            ['property' => 'id', 'direction' => 'desc'],
        ],
    ]));

    $response->assertStatus(200);

    $ids = array_column($response->json('data'), 'id');
    expect($ids)->toHaveCount(3);
    expect($ids)->toBe([$z->id, $a->id, $this->content->id]);
});

test('get contents by relation supports filtering', function (): void {
    $filtered = Content::factory()->create();
    $filtered->title = 'Filtered Content';
    $filtered->save();
    $filtered->categories()->attach($this->category->id);

    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
        'filters' => [
            [
                'property' => 'translations.title',
                'value' => 'Filtered',
                'operator' => 'like',
            ],
        ],
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['title'])->toBe('Filtered Content');
});

test('get contents by relation returns correct content structure', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => 'Test Category',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

test('get contents by relation handles multiple relations', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'contributors',
        'value' => 'Test Contributor',
        'entity' => 'contents',
    ]));

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation works with different entity types', function (): void {
    $response = $this->getJson('/api/v1/select/cms/categories/Test%20Category/posts');

    $response->assertStatus(404);
});

test('get contents by relation handles empty parameters', function (): void {
    $response = $this->getJson('/api/v1/select/cms//test-category/contents');

    $response->assertStatus(404);
});

test('get contents by relation returns proper error for invalid route', function (): void {
    $response = $this->getJson('/api/v1/invalid/1/contents');

    $response->assertStatus(404);
});
