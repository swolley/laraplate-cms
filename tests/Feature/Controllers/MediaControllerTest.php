<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
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
