<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\Filter;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Models\ACL;
use Modules\Core\Models\MediaDraft;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Services\Authorization\AuthorizationService;
use Modules\Core\Support\PermissionName;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{module: string, entity: string, id: int|string}
 */
function mediaRouteParams(Content $content): array
{
    return ['module' => 'cms', 'entity' => 'contents', 'id' => $content->id];
}

function grantContentPermission(User $user, string $operation): void
{
    $name = 'default.cms_contents.' . $operation;
    Permission::findOrCreate($name, 'web');
    $user->givePermissionTo($name);
}

/**
 * @return array{module: string, entity: string}
 */
function pendingRouteParams(): array
{
    return ['module' => 'cms', 'entity' => 'contents'];
}

/**
 * Grant the given operations to the user through a role whose ACL restricts the
 * content table to a single visible id, so every other row is hidden per-row.
 *
 * @param  list<string>  $operations
 */
function seedRowAcl(User $user, int $visibleId, array $operations): void
{
    $content = new Content;
    $table = $content->getTable();
    $connection = $content->getConnectionName() ?? 'default';
    $role = Role::findOrCreate('cms_row_acl_' . uniqid(), 'web');

    foreach ($operations as $operation) {
        $permission_name = PermissionName::build($connection, $table, $operation);
        $permission = Permission::firstOrCreate(['name' => $permission_name], ['guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $acl = new ACL;
        $acl->setSkipValidation(true);
        $acl->forceFill([
            'permission_id' => $permission->id,
            'role_id' => $role->id,
            'filters' => new FiltersGroup(
                filters: [new Filter("{$table}.id", $visibleId, FilterOperator::Equals)],
                operator: WhereClause::And,
            ),
            'priority' => 100,
            'is_active' => true,
        ]);
        $acl->save();
    }

    $user->assignRole($role);
}

beforeEach(function (): void {
    $content = new Content;

    if (! $content->getConnection()->getSchemaBuilder()->hasColumn($content->getTable(), 'shared_components')) {
        $this->markTestSkipped('Media controller integration requires full Core runtime.');
    }

    Queue::fake();
    Storage::fake(config('media-library.disk_name'));

    setupCMSEntities();

    $this->content = Content::factory()->create();
    $this->content->title = 'Media Test Content';
    $this->content->save();
});

test('lists the content media grouped by collection', function (): void {
    $file = UploadedFile::fake()->image('existing.jpg');
    $this->content
        ->addMedia($file->getRealPath())
        ->usingFileName('existing.jpg')
        ->toMediaCollection('images');

    $viewer = User::factory()->create();
    grantContentPermission($viewer, 'select');

    $response = $this->actingAs($viewer)->getJson(
        route('core.crud.media.list', mediaRouteParams($this->content)),
    );

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'images' => [
                    '*' => ['id', 'uuid', 'collection_name', 'name', 'file_name', 'mime_type', 'size', 'order_column', 'custom_properties', 'url', 'conversions'],
                ],
            ],
        ]);

    expect($response->json('data.images'))->toHaveCount(1)
        ->and($response->json('data.images.0.collection_name'))->toBe('images');
});

test('uploads a file into the images collection with update permission', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'update');

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.upload', mediaRouteParams($this->content)),
        [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'collection' => 'images',
            'name' => 'Nice photo',
        ],
    );

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.collection_name', 'images')
        ->assertJsonPath('data.name', 'Nice photo');

    expect($this->content->fresh()->getMedia('images'))->toHaveCount(1);
});

test('rejects upload to an unregistered collection with 422', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'update');

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.upload', mediaRouteParams($this->content)),
        [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'collection' => 'not-a-collection',
        ],
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    expect($this->content->fresh()->media()->count())->toBe(0);
});

test('deletes a media item with update permission', function (): void {
    $file = UploadedFile::fake()->image('to-delete.jpg');
    $media = $this->content
        ->addMedia($file->getRealPath())
        ->usingFileName('to-delete.jpg')
        ->toMediaCollection('images');

    $editor = User::factory()->create();
    grantContentPermission($editor, 'update');

    $response = $this->actingAs($editor)->deleteJson(
        route('core.crud.media.delete', [...mediaRouteParams($this->content), 'media' => $media->id]),
    );

    $response->assertOk()->assertJsonPath('data.deleted', true);
    expect($this->content->fresh()->media()->count())->toBe(0);
});

test('denies upload without update permission', function (): void {
    $viewer = User::factory()->create();
    grantContentPermission($viewer, 'select');

    $response = $this->actingAs($viewer)->postJson(
        route('core.crud.media.upload', mediaRouteParams($this->content)),
        [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'collection' => 'images',
        ],
    );

    expect($response->status())->toBeIn([Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN]);
    expect($this->content->fresh()->media()->count())->toBe(0);
});

test('denies delete without update permission', function (): void {
    $file = UploadedFile::fake()->image('keep.jpg');
    $media = $this->content
        ->addMedia($file->getRealPath())
        ->usingFileName('keep.jpg')
        ->toMediaCollection('images');

    $viewer = User::factory()->create();
    grantContentPermission($viewer, 'select');

    $response = $this->actingAs($viewer)->deleteJson(
        route('core.crud.media.delete', [...mediaRouteParams($this->content), 'media' => $media->id]),
    );

    expect($response->status())->toBeIn([Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN]);
    expect($this->content->fresh()->media()->count())->toBe(1);
});

test('allows a select-only user to list media', function (): void {
    $viewer = User::factory()->create();
    grantContentPermission($viewer, 'select');

    $response = $this->actingAs($viewer)->getJson(
        route('core.crud.media.list', mediaRouteParams($this->content)),
    );

    $response->assertOk();
});

test('rejects unauthenticated requests', function (): void {
    $response = $this->getJson(
        route('core.crud.media.list', mediaRouteParams($this->content)),
    );

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

test('returns 404 for an entity whose model does not support media', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findOrCreate('superadmin', 'web'));

    $response = $this->actingAs($viewer)->getJson(
        route('core.crud.media.list', ['module' => 'core', 'entity' => 'settings', 'id' => 1]),
    );

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('hides an ACL-restricted content row on the per-id media endpoints', function (): void {
    AuthorizationService::resetPermissionCache();

    $visible = $this->content;
    $hidden = Content::factory()->create();

    $user = User::factory()->create();
    seedRowAcl($user, $visible->id, ['select', 'update']);

    $this->actingAs($user)
        ->getJson(route('core.crud.media.list', mediaRouteParams($visible)))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('core.crud.media.list', mediaRouteParams($hidden)))
        ->assertStatus(Response::HTTP_NOT_FOUND);

    $this->actingAs($user)
        ->postJson(route('core.crud.media.upload', mediaRouteParams($hidden)), [
            'file' => UploadedFile::fake()->image('blocked.jpg'),
            'collection' => 'images',
        ])
        ->assertStatus(Response::HTTP_NOT_FOUND);

    $present = UploadedFile::fake()->image('present.jpg');
    $media = $hidden
        ->addMedia($present->getRealPath())
        ->usingFileName('present.jpg')
        ->toMediaCollection('images');

    $this->actingAs($user)
        ->deleteJson(route('core.crud.media.delete', [...mediaRouteParams($hidden), 'media' => $media->id]))
        ->assertStatus(Response::HTTP_NOT_FOUND);

    expect($hidden->fresh()->media()->count())->toBe(1);
});

test('stages a pending upload without an owner id and returns 201', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'insert');
    $token = Str::uuid()->toString();

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.pending.upload', pendingRouteParams()),
        [
            'file' => UploadedFile::fake()->image('draft.jpg'),
            'collection' => 'images',
            'token' => $token,
        ],
    );

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.collection_name', 'pending');

    $draft = MediaDraft::query()->where('token', $token)->first();

    expect($draft)->not->toBeNull()
        ->and($draft->user_id)->toBe($editor->id)
        ->and($draft->media()->count())->toBe(1)
        ->and($draft->media()->first()->getCustomProperty('target_collection'))->toBe('images');
});

test('rejects a pending upload to an unregistered collection with 422', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'insert');

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.pending.upload', pendingRouteParams()),
        [
            'file' => UploadedFile::fake()->image('draft.jpg'),
            'collection' => 'not-a-collection',
            'token' => Str::uuid()->toString(),
        ],
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    expect(MediaDraft::query()->count())->toBe(0);
});

test('lists and deletes the user pending media', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'insert');
    $token = Str::uuid()->toString();

    $upload = $this->actingAs($editor)->postJson(
        route('core.crud.media.pending.upload', pendingRouteParams()),
        [
            'file' => UploadedFile::fake()->image('a.jpg'),
            'collection' => 'images',
            'token' => $token,
        ],
    );
    $upload->assertStatus(Response::HTTP_CREATED);
    $media_id = $upload->json('data.id');

    $list = $this->actingAs($editor)->getJson(
        route('core.crud.media.pending.list', [...pendingRouteParams(), 'token' => $token]),
    );
    $list->assertOk();
    expect($list->json('data.images'))->toHaveCount(1);

    $delete = $this->actingAs($editor)->deleteJson(
        route('core.crud.media.pending.delete', [...pendingRouteParams(), 'media' => $media_id, 'token' => $token]),
    );
    $delete->assertOk()->assertJsonPath('data.deleted', true);

    expect(MediaDraft::query()->where('token', $token)->first()->media()->count())->toBe(0);
});

test('claims pending media onto a freshly created content and empties the draft', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'insert');
    grantContentPermission($editor, 'update');
    $token = Str::uuid()->toString();

    $this->actingAs($editor)->postJson(
        route('core.crud.media.pending.upload', pendingRouteParams()),
        [
            'file' => UploadedFile::fake()->image('claim-me.jpg'),
            'collection' => 'images',
            'token' => $token,
        ],
    )->assertStatus(Response::HTTP_CREATED);

    $target = Content::factory()->create();

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.claim', [...pendingRouteParams(), 'id' => $target->id]),
        ['token' => $token],
    );

    $response->assertOk();
    expect($response->json('data.images'))->toHaveCount(1)
        ->and($target->fresh()->getMedia('images'))->toHaveCount(1)
        ->and(MediaDraft::query()->where('token', $token)->exists())->toBeFalse();
});

test('claim with a missing token moves nothing and returns empty data', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'update');
    $target = Content::factory()->create();

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.claim', [...pendingRouteParams(), 'id' => $target->id]),
        ['token' => Str::uuid()->toString()],
    );

    $response->assertOk()->assertJsonPath('data', []);
    expect($target->fresh()->media()->count())->toBe(0);
});

test('denies claim without update permission', function (): void {
    $editor = User::factory()->create();
    grantContentPermission($editor, 'insert');
    $target = Content::factory()->create();

    $response = $this->actingAs($editor)->postJson(
        route('core.crud.media.claim', [...pendingRouteParams(), 'id' => $target->id]),
        ['token' => Str::uuid()->toString()],
    );

    expect($response->status())->toBeIn([Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN]);
});

test('prune command deletes stale drafts and keeps fresh ones', function (): void {
    $stale = MediaDraft::factory()->create();
    $stale->created_at = now()->subDays(2);
    $stale->save();

    $fresh = MediaDraft::factory()->create();

    $this->artisan('core:prune-media-drafts')->assertSuccessful();

    expect(MediaDraft::query()->whereKey($stale->id)->exists())->toBeFalse()
        ->and(MediaDraft::query()->whereKey($fresh->id)->exists())->toBeTrue();
});
