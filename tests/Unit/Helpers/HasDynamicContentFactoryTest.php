<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Content;
use Modules\Cms\Tests\TestCase;
use Modules\Core\Overrides\Factory as CoreFactory;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCmsEntities([EntityType::CONTENTS]);
});

it('returns entity and presettable ids from dynamicContentDefinition', function (): void {
    $definition = Content::factory()->dynamicContentDefinition();

    expect($definition)->toHaveKeys(['entity_id', 'presettable_id'])
        ->and($definition['entity_id'])->toBeInt()
        ->and($definition['presettable_id'])->toBeInt();
});

it('throws when entityType is missing on the factory', function (): void {
    $factory = new class extends CoreFactory
    {
        protected $model = Content::class;

        protected function definitionsArray(): array
        {
            return [];
        }
    };

    $method = new ReflectionMethod($factory, 'entityTypeForDynamicContent');
    $method->setAccessible(true);

    $method->invoke($factory, Content::class);
})->throws(\RuntimeException::class);

it('returns early from createDynamicContentRelations when callback is null', function (): void {
    $content = Content::factory()->make();

    Content::factory()->createDynamicContentRelations($content, null);

    expect(true)->toBeTrue();
});

it('rethrows when the callback fails', function (): void {
    $first = Content::factory()->make(['id' => 1]);
    $collection = new EloquentCollection([$first]);

    expect(fn () => Content::factory()->createDynamicContentRelations($collection, static function (): void {
        throw new RuntimeException('attach failed');
    }))->toThrow(\RuntimeException::class);
});
