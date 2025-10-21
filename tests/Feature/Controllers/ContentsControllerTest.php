<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Author;
use Modules\Core\Models\User;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    $this->category = Category::factory()->create(['name' => 'Test Category']);
    $this->author = Author::factory()->create(['name' => 'Test Author']);
    $this->content = Content::factory()->create([
        'title' => 'Test Content',
        'category_id' => $this->category->id,
        'author_id' => $this->author->id,
    ]);
});

test('get contents by relation returns contents for category', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'title', 'content', 'category_id', 'author_id',
                ],
            ],
        ]);
});

test('get contents by relation filters by category', function (): void {
    $otherCategory = Category::factory()->create(['name' => 'Other Category']);
    $otherContent = Content::factory()->create([
        'title' => 'Other Content',
        'category_id' => $otherCategory->id,
    ]);
    
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation filters by author', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'authors',
        'value' => $this->author->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->content->id);
});

test('get contents by relation handles singular entity names', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'content', // singular
    ]));
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', 'title', 'content',
                ],
            ],
        ]);
});

test('get contents by relation returns empty array when no contents', function (): void {
    $emptyCategory = Category::factory()->create(['name' => 'Empty Category']);
    
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $emptyCategory->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200)
        ->assertJson([
            'data' => [],
        ]);
});

test('get contents by relation handles invalid relation', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'invalid',
        'value' => '1',
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200);
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
    // Create multiple contents
    Content::factory()->count(5)->create([
        'category_id' => $this->category->id,
    ]);
    
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'contents',
        'page' => 1,
        'per_page' => 3,
    ]));
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [],
            'meta' => [
                'current_page',
                'per_page',
                'total',
            ],
        ]);
});

test('get contents by relation supports sorting', function (): void {
    Content::factory()->create([
        'title' => 'A Content',
        'category_id' => $this->category->id,
    ]);
    Content::factory()->create([
        'title' => 'Z Content',
        'category_id' => $this->category->id,
    ]);
    
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'contents',
        'sort' => 'title',
        'order' => 'asc',
    ]));
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toHaveCount(3); // 2 new + 1 existing
    expect($data[0]['title'])->toBe('A Content');
});

test('get contents by relation supports filtering', function (): void {
    Content::factory()->create([
        'title' => 'Filtered Content',
        'category_id' => $this->category->id,
    ]);
    
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'contents',
        'filters' => [
            [
                'property' => 'title',
                'value' => 'Filtered',
                'operator' => 'contains',
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
        'value' => $this->category->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'content',
                    'category_id',
                    'author_id',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

test('get contents by relation handles multiple relations', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'authors',
        'value' => $this->author->id,
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['author_id'])->toBe($this->author->id);
});

test('get contents by relation works with different entity types', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => 'categories',
        'value' => $this->category->id,
        'entity' => 'posts', // different entity
    ]));
    
    $response->assertStatus(200);
});

test('get contents by relation handles empty parameters', function (): void {
    $response = $this->getJson(route('cms.api.relation.contents', [
        'relation' => '',
        'value' => '',
        'entity' => 'contents',
    ]));
    
    $response->assertStatus(200);
});

test('get contents by relation returns proper error for invalid route', function (): void {
    $response = $this->getJson('/api/v1/invalid/1/contents');
    
    $response->assertStatus(404);
});
