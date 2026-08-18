<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    /** @var TestCase $this */
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents]);
    $this->actingAs(User::factory()->create());
});

it('returns the tag co-occurrence graph over the endpoint', function (): void {
    /** @var TestCase $this */
    $cinema = Tag::factory()->create();
    $cinema->translations()->where('locale', 'en')->update(['name' => 'Cinema']);
    $drama = Tag::factory()->create();
    $drama->translations()->where('locale', 'en')->update(['name' => 'Drama']);

    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->tags()->sync([$cinema->id, $drama->id]);

    $response = $this->getJson(route('cms.insights.tag-graph'));

    $response->assertOk()
        ->assertJsonCount(2, 'data.nodes')
        ->assertJsonCount(1, 'data.edges')
        ->assertJsonPath('data.edges.0.weight', 1);
});
