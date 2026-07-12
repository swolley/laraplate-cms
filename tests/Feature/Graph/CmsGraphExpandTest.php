<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('core.expose_crud_api', true);
    setupCMSEntities();
});

it('expands cms content tags when the relation is requested', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('superadmin', 'web'));

    $content = Content::factory()->create(['title' => 'Graph Content', 'valid_from' => now()->subDay(), 'valid_to' => null]);
    $tag = Tag::factory()->create();
    $content->tags()->attach($tag->getKey());

    $this->actingAs($user)
        ->getJson('/api/v1/crud/graph/expand/CMS/contents/' . $content->getKey() . '?relations[]=tags&node_detail=summary')
        ->assertOk()
        ->assertJsonPath('data.center', 'cms:contents:' . $content->getKey())
        ->assertJsonPath('data.graphMeta.requestedRelations.0', 'tags')
        ->assertJsonFragment([
            'id' => 'cms:tags:' . $tag->getKey(),
            'module' => 'cms',
            'entity' => 'tags',
        ]);
});

it('uses provider defaults when relations are absent', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('superadmin', 'web'));

    $content = Content::factory()->create(['title' => 'Default Graph Content', 'valid_from' => now()->subDay(), 'valid_to' => null]);
    $tag = Tag::factory()->create();
    $content->tags()->attach($tag->getKey());

    $this->actingAs($user)
        ->getJson('/api/v1/crud/graph/expand/CMS/contents/' . $content->getKey())
        ->assertOk()
        ->assertJsonPath('data.graphMeta.requestedRelations.0', 'tags')
        ->assertJsonFragment([
            'id' => 'cms:tags:' . $tag->getKey(),
        ]);
});
