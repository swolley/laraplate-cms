<?php

declare(strict_types=1);

use Modules\CMS\Services\ContentExtenderRegistry;
use Modules\CMS\Tests\Stubs\ContentExtension\FakeContentExtender;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('registers and resolves an extender by its alias', function (): void {
    $registry = new ContentExtenderRegistry();
    $registry->register('cms.fake_extender', FakeContentExtender::class);

    expect($registry->has('cms.fake_extender'))->toBeTrue()
        ->and($registry->resolve('cms.fake_extender'))->toBe(FakeContentExtender::class)
        ->and($registry->aliases())->toBe(['cms.fake_extender']);
});

it('reports unknown aliases as absent', function (): void {
    $registry = new ContentExtenderRegistry();

    expect($registry->has('cms.unknown'))->toBeFalse();
});

it('fails loud when resolving an unregistered alias', function (): void {
    $registry = new ContentExtenderRegistry();

    $registry->resolve('cms.unknown');
})->throws(InvalidArgumentException::class, 'No content extender registered for alias [cms.unknown].');

it('is a container singleton and starts empty', function (): void {
    $first = app(ContentExtenderRegistry::class);
    $second = app(ContentExtenderRegistry::class);

    expect($first)->toBe($second)
        ->and($first->aliases())->toBe([]);
});
