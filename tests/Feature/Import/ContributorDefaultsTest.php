<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Support\ContributorDefaults;
use Modules\CMS\Import\Support\DefaultContributorProvisioner;
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

it('reuses the default contributor on subsequent imports without creating duplicates', function (): void {
    $first_id = resolve(ContributorDefaults::class)->resolveContributorId();

    resolve(ImportIdMap::class)->reset();

    $second_id = resolve(ContributorDefaults::class)->resolveContributorId();

    expect($second_id)->toBe($first_id)
        ->and(Contributor::query()->count())->toBe(1);
});

it('creates the default contributor without any record origin', function (): void {
    $resolved_id = resolve(ContributorDefaults::class)->resolveContributorId();

    expect($resolved_id)->toBeInt()
        ->and(Contributor::query()->findOrFail($resolved_id)->name)->toBe('Redazione')
        ->and(RecordOrigin::query()
            ->where('referable_type', Contributor::class)
            ->where('referable_id', $resolved_id)
            ->exists())->toBeFalse();
});

it('provisions the contributor preset from bindings when it is missing', function (): void {
    config([
        'cms.import.bindings.contributors.contributor' => [
            'entity' => 'contributor',
            'preset' => 'default',
        ],
    ]);

    $resolved_id = resolve(DefaultContributorProvisioner::class)->ensure();

    expect($resolved_id)->toBeInt()
        ->and(\Modules\CMS\Models\Entity::query()->where('name', 'contributors')->exists())->toBeTrue()
        ->and(\Modules\CMS\Models\Preset::query()
            ->whereHas('entity', fn ($query) => $query->where('name', 'contributors'))
            ->where('name', 'default')
            ->exists())->toBeTrue();
});

it('reuses an existing contributor imported from another source when slug matches', function (): void {
    config(['cms.import.locale' => 'it']);

    $contributor = Contributor::factory()->create(['name' => 'Redazione']);
    $contributor->setTranslation('it', ['slug' => 'redazione', 'components' => []]);
    $contributor->save();

    resolve(\Modules\CMS\Import\Support\ExternalReferenceLocator::class)
        ->register($contributor, 'naxos_api', 74);

    $resolved_id = resolve(ContributorDefaults::class)->resolveContributorId();

    expect($resolved_id)->toBe($contributor->id)
        ->and(Contributor::query()->count())->toBe(1)
        ->and(RecordOrigin::query()
            ->where('referable_type', Contributor::class)
            ->where('referable_id', $contributor->id)
            ->where('source_key', 'cms_default')
            ->exists())->toBeFalse();
});
