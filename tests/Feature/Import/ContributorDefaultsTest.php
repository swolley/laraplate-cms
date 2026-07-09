<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Support\ContributorDefaults;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);

    if (! Schema::hasTable(CMSTables::Contributors->value)) {
        $this->markTestSkipped('CMS contributor defaults tests require full schema.');
    }

    setupCMSEntities([EntityType::Contributors]);
    DynamicContentsService::reset();
    resolve(ImportIdMap::class)->reset();
});

it('reuses the default contributor from core_record_origins on subsequent imports', function (): void {
    $first_id = resolve(ContributorDefaults::class)->resolveContributorId();

    resolve(ImportIdMap::class)->reset();

    $second_id = resolve(ContributorDefaults::class)->resolveContributorId();

    expect($second_id)->toBe($first_id)
        ->and(Contributor::query()->count())->toBe(1)
        ->and(RecordOrigin::query()
            ->where('referable_type', Contributor::class)
            ->where('source_key', 'cms_default')
            ->where('external_id', '1')
            ->count())->toBe(1);
});

it('creates and registers the default contributor when no origin exists', function (): void {
    $resolved_id = resolve(ContributorDefaults::class)->resolveContributorId();

    expect($resolved_id)->toBeInt()
        ->and(RecordOrigin::query()
            ->where('referable_type', Contributor::class)
            ->where('source_key', 'cms_default')
            ->where('external_id', '1')
            ->exists())->toBeTrue();
});
