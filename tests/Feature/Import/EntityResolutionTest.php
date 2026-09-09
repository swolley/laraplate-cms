<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ImportEntityNames;
use Modules\CMS\Import\Support\ImportPresetProvisioner;
use Modules\CMS\Models\Entity;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Services\DynamicContentsService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config(['scout.driver' => 'null']);

    $entity = new Entity;

    if (! $entity->getConnection()->getSchemaBuilder()->hasTable($entity->getTable())) {
        $this->markTestSkipped('CMS entity resolution tests require full schema.');
    }

    Entity::query()->withoutGlobalScopes()->delete();
    DynamicContentsService::reset();
});

function make_entity(string $name, string $type, bool $is_default = false): Entity
{
    $entity = new Entity;
    $entity->setSkipValidation(true);
    $entity->forceFill([
        'name' => $name,
        'slug' => $name,
        'type' => $type,
        'is_default' => $is_default,
    ])->save();

    return $entity;
}

it('uses the only entity of the requested type, whatever the project called it', function (): void {
    $post = make_entity('post', 'contents');

    $resolved = resolve(EntityPresetResolver::class)->entityId(ImportEntityNames::CONTENTS);

    expect($resolved)->toBe((int) $post->getKey());
});

it('uses the entity the importer asked for by name when the type holds several', function (): void {
    make_entity('post', 'contents');
    $event = make_entity('event', 'contents');

    $resolved = resolve(EntityPresetResolver::class)->entityId(ImportEntityNames::CONTENTS, null, 'event');

    expect($resolved)->toBe((int) $event->getKey());
});

it('falls back to the default entity when the requested name is not there', function (): void {
    make_entity('post', 'contents');
    $fallback = make_entity('article', 'contents', is_default: true);

    $resolved = resolve(EntityPresetResolver::class)->entityId(ImportEntityNames::CONTENTS, null, 'missing');

    expect($resolved)->toBe((int) $fallback->getKey());
});

it('refuses to guess when the type holds several entities and none is named or default', function (): void {
    make_entity('post', 'contents');
    make_entity('event', 'contents');

    expect(fn (): int => resolve(EntityPresetResolver::class)->entityId(ImportEntityNames::CONTENTS))
        ->toThrow(RuntimeException::class, 'Cannot tell which CMS entity of type [contents]');
});

it('stops the import when no entity exists for the type', function (): void {
    expect(fn (): int => resolve(EntityPresetResolver::class)->entityId(ImportEntityNames::CATEGORIES))
        ->toThrow(RuntimeException::class, 'No CMS entity exists for type [categories]');
});

it('normalizes singular aliases to the entity type', function (): void {
    $category = make_entity('category', 'categories');

    $resolved = resolve(EntityPresetResolver::class)->entityId('section');

    expect($resolved)->toBe((int) $category->getKey());
});

it('never creates an entity while provisioning a preset', function (): void {
    make_entity('post', 'contents');

    resolve(ImportPresetProvisioner::class)->ensurePreset(ImportEntityNames::CONTENTS, 'naxos_post');

    expect(Entity::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(Entity::query()->where('name', 'contents')->exists())->toBeFalse();
});

it('provisions the preset on the resolved entity', function (): void {
    $post = make_entity('post', 'contents');

    $presettable = resolve(ImportPresetProvisioner::class)->ensurePreset(ImportEntityNames::CONTENTS, 'naxos_post');

    expect((int) $presettable->getAttribute('entity_id'))->toBe((int) $post->getKey());
});

it('stops provisioning when the type has no entity', function (): void {
    expect(fn () => resolve(ImportPresetProvisioner::class)->ensurePreset(ImportEntityNames::CONTRIBUTORS, 'naxos_contributor'))
        ->toThrow(RuntimeException::class, 'No CMS entity exists for type [contributors]');
});
