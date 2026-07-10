<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('normalizes legacy importer entity names to canonical cms entities', function (): void {
    $resolver = resolve(EntityPresetResolver::class);

    expect(ImportEntityNames::normalize('post'))->toBe(ImportEntityNames::CONTENTS)
        ->and(ImportEntityNames::normalize('event'))->toBe(ImportEntityNames::CONTENTS)
        ->and(ImportEntityNames::normalize('category'))->toBe(ImportEntityNames::CATEGORIES)
        ->and(ImportEntityNames::normalize('contributor'))->toBe(ImportEntityNames::CONTRIBUTORS)
        ->and(ImportEntityNames::normalize('contents'))->toBe(ImportEntityNames::CONTENTS);

    setupCMSEntities();

    expect($resolver->entityId('post'))->toBe($resolver->entityId('contents'))
        ->and($resolver->entityId('contributor'))->toBe($resolver->entityId('contributors'));
});
