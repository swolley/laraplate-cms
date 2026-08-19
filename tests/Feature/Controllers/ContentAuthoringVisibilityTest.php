<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;

uses(TestCase::class, RefreshDatabase::class);

function authoringSuperadmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(config('permission.roles.superadmin'), 'web'));

    return $user;
}

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);

    // A scheduled (future valid_from) content: excluded by the validity scope.
    $this->draft = Content::factory()->create(['valid_from' => now()->addWeek(), 'valid_to' => null]);
});

it('hides out-of-validity contents by default (public / API surface)', function (): void {
    $live = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    expect(Content::query()->find($this->draft->id))->toBeNull()
        ->and(Content::query()->find($live->id))->not->toBeNull();
});

it('shows out-of-validity contents when the authoring surface is active', function (): void {
    expect(Content::query()->find($this->draft->id))->toBeNull();

    authoring_surface(true);

    expect(Content::query()->find($this->draft->id))->not->toBeNull();
});

it('returns a scheduled content over the session app CRUD list route', function (): void {
    $response = $this->actingAs(authoringSuperadmin())->getJson(
        route('core.crud.list', ['module' => 'cms', 'entity' => 'contents']),
    );

    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($this->draft->id);
});

it('can update a scheduled content by id over the app CRUD', function (): void {
    $response = $this->actingAs(authoringSuperadmin())->patchJson(
        route('core.crud.replace', ['module' => 'cms', 'entity' => 'contents']),
        ['id' => $this->draft->id, 'valid_from' => now()->subDay()->toDateTimeString()],
    );

    $response->assertOk();
});
