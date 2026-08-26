<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contributors]);
});

it('creates contributors with unique names', function (): void {
    $contributors = Contributor::factory()->count(25)->create();

    $names = $contributors->pluck('name');

    expect($names->unique()->count())->toBe($names->count());
});

it('creates unique names when similar contributor names already exist', function (): void {
    $existing_name = 'Seeded Contributor-' . getmypid() . '-23994788';
    Contributor::factory()->create(['name' => $existing_name]);

    $contributors = Contributor::factory()->count(15)->create();

    expect($contributors->pluck('name'))->not->toContain($existing_name)
        ->and($contributors->pluck('name')->unique()->count())->toBe(15);
});
