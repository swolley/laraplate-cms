<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Contributor;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Cms\Models\Preset;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    foreach ([EntityType::CONTRIBUTORS, EntityType::CONTENTS] as $entityType) {
        $name = mb_strtolower($entityType->value);
        $entity = Entity::firstOrCreate(['name' => $name], ['type' => $entityType]);
        $preset = Preset::firstOrCreate(['entity_id' => $entity->id, 'name' => 'default']);
        Presettable::firstOrCreate(['entity_id' => $entity->id, 'preset_id' => $preset->id]);
    }

    $this->contributor = Contributor::factory()->create();
});

it('can be created with factory', function (): void {
    expect($this->contributor)->toBeInstanceOf(Contributor::class);
    expect($this->contributor->id)->not->toBeNull();
});

it('has fillable attributes', function (): void {
    $contributorData = [
        'name' => 'John Doe',
    ];

    $contributor = Contributor::create($contributorData);

    expect($contributor->name)->toBe('John Doe');
});

it('has hidden attributes', function (): void {
    $contributor = Contributor::factory()->create();
    $contributorArray = $contributor->toArray();

    expect($contributorArray)->not->toHaveKey('user_id');
    expect($contributorArray)->not->toHaveKey('user');
    expect($contributorArray)->not->toHaveKey('created_at');
    expect($contributorArray)->not->toHaveKey('updated_at');
});

it('belongs to many contents', function (): void {
    $content1 = Content::factory()->create(['title' => 'Article 1']);
    $content2 = Content::factory()->create(['title' => 'Article 2']);

    $this->contributor->contents()->attach([$content1->id, $content2->id]);

    expect($this->contributor->contents)->toHaveCount(2);
    expect($this->contributor->contents->pluck('title')->toArray())->toContain('Article 1', 'Article 2');
});

it('has dynamic contents trait', function (): void {
    expect(method_exists($this->contributor, 'getComponentsAttribute'))->toBeTrue();
    expect(method_exists($this->contributor, 'mergeComponentsValues'))->toBeTrue();
});

it('has multimedia trait', function (): void {
    expect(method_exists($this->contributor, 'addMedia'))->toBeTrue();
    expect(method_exists($this->contributor, 'getMedia'))->toBeTrue();
});

it('has tags trait', function (): void {
    expect(method_exists($this->contributor, 'attachTags'))->toBeTrue();
    expect(method_exists($this->contributor, 'detachTags'))->toBeTrue();
});

it('has versions trait', function (): void {
    expect(method_exists($this->contributor, 'versions'))->toBeTrue();
    expect(method_exists($this->contributor, 'createVersion'))->toBeTrue();
});

it('has soft deletes trait', function (): void {
    $this->contributor->delete();

    expect($this->contributor->trashed())->toBeTrue();
    expect(Contributor::withTrashed()->find($this->contributor->id))->not->toBeNull();
});

it('has validations trait', function (): void {
    expect(method_exists($this->contributor, 'getRules'))->toBeTrue();
});

it('can be created with specific attributes', function (): void {
    $contributorData = [
        'name' => 'Jane Smith',
    ];

    $contributor = Contributor::create($contributorData);

    expect($contributor->name)->toBe('Jane Smith');
});

it('can be found by name', function (): void {
    $contributor = Contributor::factory()->create(['name' => 'Unique Contributor']);

    $foundContributor = Contributor::where('name', 'Unique Contributor')->first();

    expect($foundContributor->id)->toBe($contributor->id);
});

it('has proper timestamps', function (): void {
    $contributor = Contributor::factory()->create();

    expect($contributor->created_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
    expect($contributor->updated_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
});

it('can be serialized to array', function (): void {
    $contributor = Contributor::factory()->create([
        'name' => 'Test Contributor',
    ]);
    $contributorArray = $contributor->toArray();

    expect($contributorArray)->toHaveKey('id');
    expect($contributorArray)->toHaveKey('name');
    expect($contributorArray['name'])->toBe('Test Contributor');
});

it('can be restored after soft delete', function (): void {
    $contributor = Contributor::factory()->create();
    $contributor->delete();

    expect($contributor->trashed())->toBeTrue();

    $contributor->restore();

    expect($contributor->trashed())->toBeFalse();
});

it('can be permanently deleted', function (): void {
    $contributor = Contributor::factory()->create();
    $contributorId = $contributor->id;

    $contributor->forceDelete();

    expect(Contributor::withTrashed()->find($contributorId))->toBeNull();
});
