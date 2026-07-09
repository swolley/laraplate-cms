<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Import\Dto\ImportLocationDto;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Upserters\LocationUpserter;
use Modules\CMS\Jobs\GeocodeLocationJob;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);
    Queue::fake();

    if (! Schema::hasTable(CMSTables::Locations->value)) {
        $this->markTestSkipped('CMS location upserter tests require full schema.');
    }
});

it('creates imported locations using the country from the dto', function (): void {
    $location_id = resolve(LocationUpserter::class)->upsert(new ImportLocationDto(
        name: 'Palazzo Ducale',
        slug: 'palazzo-ducale',
        externalId: 12,
        sourceType: 'naxos_api_event@tenant',
        country: 'Italia',
    ));

    $location = Location::query()->findOrFail($location_id);

    expect($location->name)->toBe('Palazzo Ducale')
        ->and($location->country)->toBe('Italia');

    Queue::assertPushed(GeocodeLocationJob::class);
});

it('rejects locations without a country on the dto', function (): void {
    resolve(LocationUpserter::class)->upsert(new ImportLocationDto(
        name: 'Palazzo Ducale',
        slug: 'palazzo-ducale',
        externalId: 12,
        sourceType: 'naxos_api_event@tenant',
    ));
})->throws(InvalidArgumentException::class, 'ImportLocationDto::country is required');

it('reuses an existing location from another source when the name matches', function (): void {
    $existing = Location::factory()->create([
        'name' => 'Strà',
        'slug' => 'str',
    ]);

    resolve(ExternalReferenceLocator::class)->register(
        $existing,
        'naxos_api_event@naxos-liberta-it',
        415,
    );

    $resolved_id = resolve(LocationUpserter::class)->upsert(new ImportLocationDto(
        name: 'Strà',
        slug: 'stra',
        externalId: 99,
        sourceType: 'naxos_api_event@naxos-lanuova-net',
        country: 'Italia',
    ));

    expect($resolved_id)->toBe($existing->id)
        ->and(Location::query()->count())->toBe(1)
        ->and(RecordOrigin::query()
            ->where('referable_type', Location::class)
            ->where('referable_id', $existing->id)
            ->where('source_key', 'naxos_api_event@naxos-lanuova-net')
            ->where('external_id', '99')
            ->exists())->toBeTrue();
});

it('reuses an existing location from another source when the slug matches', function (): void {
    $existing = Location::factory()->create([
        'name' => 'Teatro Comunale',
        'slug' => 'teatro-comunale',
    ]);

    resolve(ExternalReferenceLocator::class)->register(
        $existing,
        'naxos_api_event@tenant-a',
        10,
    );

    $resolved_id = resolve(LocationUpserter::class)->upsert(new ImportLocationDto(
        name: 'Teatro Comunale di Parma',
        slug: 'teatro-comunale',
        externalId: 11,
        sourceType: 'naxos_api_event@tenant-b',
        country: 'Italia',
    ));

    expect($resolved_id)->toBe($existing->id)
        ->and(Location::query()->count())->toBe(1);
});
