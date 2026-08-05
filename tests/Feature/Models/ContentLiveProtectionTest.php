<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $modifications
 */
function contentRequiresApproval(Content $content, array $modifications): bool
{
    $method = new ReflectionMethod(Content::class, 'requiresApprovalWhen');
    $method->setAccessible(true);

    return (bool) $method->invoke($content, $modifications);
}

beforeEach(function (): void {
    $app = App::getFacadeRoot();
    $mock = Mockery::mock($app)->makePartial();
    $mock->shouldReceive('runningInConsole')->andReturn(false);
    App::swap($mock);
});

afterEach(function (): void {
    Mockery::close();
});

it('does not require approval for unpublished content edits', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $content = new Content([
        'valid_from' => null,
        'valid_to' => null,
    ]);

    expect(contentRequiresApproval($content, ['title' => 'Changed']))->toBeFalse();
});

it('requires approval for live content edits without approve permission', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $content = new Content([
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ]);

    expect(contentRequiresApproval($content, ['title' => 'Changed']))->toBeTrue();
});

it('requires approval for scheduled content edits without approve permission', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $content = new Content([
        'valid_from' => now()->addDay(),
        'valid_to' => null,
    ]);

    expect(contentRequiresApproval($content, ['title' => 'Changed']))->toBeTrue();
});

it('does not require approval for expired content edits', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $content = new Content([
        'valid_from' => now()->subDays(10),
        'valid_to' => now()->subDay(),
    ]);

    expect(contentRequiresApproval($content, ['title' => 'Changed']))->toBeFalse();
});

it('does not require approval for live edits when user can approve the table', function (): void {
    $user = User::factory()->create();
    $role = Role::findOrCreate('superadmin', 'web');
    $user->assignRole($role);
    Auth::login($user);

    $content = new Content([
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ]);

    expect(contentRequiresApproval($content, ['title' => 'Changed']))->toBeFalse();
});
