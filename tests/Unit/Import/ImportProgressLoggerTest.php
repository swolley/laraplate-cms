<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Modules\CMS\Import\Dto\ImportContentDto;
use Modules\CMS\Import\Support\ImportProgressLogger;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('logs a new content import with the origin url', function (): void {
    Log::spy();

    $dto = new ImportContentDto(
        title: 'Sample article',
        slug: 'sample-article',
        components: [],
        sharedComponents: [],
        validFrom: null,
        validTo: null,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        externalId: 42,
        externalUuid: null,
        sourceType: 'naxos_api',
        originUrl: 'https://example.test/articles/sample-article',
    );

    (new ImportProgressLogger)->contentImported($dto, created: true);

    Log::shouldHaveReceived('info')
        ->with('imported new content from original url https://example.test/articles/sample-article')
        ->once();
});

it('logs an updated content import and falls back when origin url is missing', function (): void {
    Log::spy();

    $dto = new ImportContentDto(
        title: 'Sample article',
        slug: 'sample-article',
        components: [],
        sharedComponents: [],
        validFrom: null,
        validTo: null,
        createdAt: null,
        updatedAt: null,
        deletedAt: null,
        externalId: 42,
        externalUuid: null,
        sourceType: 'naxos_sql',
    );

    (new ImportProgressLogger)->contentImported($dto, created: false);

    Log::shouldHaveReceived('info')
        ->with('updated content from original url naxos_sql#42')
        ->once();
});
