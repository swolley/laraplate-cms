<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\ContentReference;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Support\CrudApiExposure;
use Modules\Core\Support\PermissionName;

uses(TestCase::class, RefreshDatabase::class);

/*
 * A part of its parent (the references of a content) inherits the parent's visibility: hydrating it asks the
 * parent's select permission, within the token's abilities, never the part's own. A caller who reads the content
 * reads its references, whether or not its role or its token names the references' permission; a caller who
 * may not read the parent does not read its parts.
 */

beforeEach(function (): void {
    Cache::flush();
    config()->set('core.search.vector.enabled', false);
    CrudApiExposure::enable();
    setupCMSEntities([EntityType::Contents]);
    Artisan::call('permission:refresh', ['--quiet' => true]);
});

/**
 * @param  list<class-string<Illuminate\Database\Eloquent\Model>>  $model_classes
 */
function partsparent_user(string $guard, array $model_classes): User
{
    $role = Role::factory()->create(['name' => 'partsparent_' . uniqid(), 'guard_name' => $guard]);

    foreach ($model_classes as $model_class) {
        $role->givePermissionTo(Permission::query()->where(['name' => PermissionName::forClass($model_class, 'select'), 'guard_name' => $guard])->firstOrFail());
    }

    $user = User::query()->findOrFail(Modules\Core\Models\User::factory()->create()->getKey());
    $user->assignRole($role);

    return $user->fresh();
}

function partsparent_content(): Content
{
    $content = Content::factory()->create();
    ContentReference::factory()->create(['content_id' => $content->id, 'label' => 'partsparent-reference']);

    return $content;
}

it('loads the references of a content for a token carrying only the content ability', function (): void {
    $content = partsparent_content();
    $user = partsparent_user('api', [Content::class, ContentReference::class]);
    $token = $user->createToken('partsparent', [PermissionName::forClass(Content::class, 'select')])->plainTextToken;

    $response = $this->getJson(
        '/api/v1/detail/cms/contents?' . http_build_query(['id' => $content->id, 'relations' => ['references']]),
        ['Authorization' => 'Bearer ' . $token],
    );

    $response->assertOk();
    expect(collect($response->json('data.references'))->pluck('label')->all())->toBe(['partsparent-reference']);
});

it('loads the references of a content for a session role holding only the content permission', function (): void {
    $content = partsparent_content();
    $user = partsparent_user('web', [Content::class]);

    $response = $this->actingAs($user)->getJson('/app/crud/detail/cms/contents?' . http_build_query(['id' => $content->id, 'relations' => ['references']]));

    $response->assertOk();
    expect(collect($response->json('data.references'))->pluck('label')->all())->toBe(['partsparent-reference']);
});

it('does not hydrate a reference for a caller who may not read its content', function (): void {
    $reference = partsparent_content()->references()->firstOrFail();
    Auth::login(partsparent_user('web', [ContentReference::class]));

    expect(fn (): ?ContentReference => ContentReference::query()->find($reference->getKey()))->toThrow(AuthorizationException::class);
});
