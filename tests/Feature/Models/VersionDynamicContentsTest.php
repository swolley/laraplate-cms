<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\User;
use Modules\Core\Models\Version;

uses(TestCase::class, RefreshDatabase::class);

/*
 * The history of a content or a contributor shows the changes of its dynamic fields to an editor: the columns
 * that store them (`components`, `shared_components`) are hidden by the model only to be flattened in its own
 * serialization, they are not secrets. The anonymous user still never reads what the model hides from it.
 */

/**
 * @param  array<string, mixed>  $contents
 */
function versiondyn_version(string $model_class, array $contents): Version
{
    $version = new Version();
    $version->setRawAttributes([
        'id' => 1,
        'versionable_type' => (new $model_class())->getMorphClass(),
        'versionable_id' => 1,
        'contents' => json_encode($contents),
        'original_contents' => json_encode($contents),
    ]);

    return $version;
}

it('shows an editor the dynamic fields of a content and of a contributor in their history', function (): void {
    Auth::setUser(User::factory()->create());
    $contents = ['components' => ['subtitle' => 'versiondyn-subtitle'], 'shared_components' => ['phone' => 'versiondyn-phone']];

    foreach ([Content::class, Contributor::class] as $model_class) {
        $serialized = versiondyn_version($model_class, $contents)->toArray();

        expect($serialized['contents'])->toBe($contents)
            ->and($serialized['original_contents'])->toBe($contents);
    }
});

it('still masks the contact data of a contributor for the anonymous user', function (): void {
    Cache::flush();
    Auth::setUser(User::factory()->create(['name' => config('permission.users.guest'), 'username' => config('permission.users.guest')]));

    $serialized = versiondyn_version(Contributor::class, ['shared_components' => ['phone' => 'versiondyn-phone']])->toArray();

    expect($serialized['contents']['shared_components'])->toBe('[hidden]');
});
