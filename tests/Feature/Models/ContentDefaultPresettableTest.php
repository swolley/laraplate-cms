<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Entity;
use Modules\Core\Models\Pivot\Presettable;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $content = new Content;

    if (! $content->getConnection()->getSchemaBuilder()->hasColumn($content->getTable(), 'shared_components')) {
        $this->markTestSkipped('Dynamic-content default presettable requires the full Core runtime.');
    }

    setupCMSEntities([EntityType::Contents]);
});

it('resolves the default presettable from the entity type, not the prefixed table name', function (): void {
    $content = new Content;

    // Previously fed the prefixed table name (cms_contents) to a backed enum whose
    // values are unprefixed, always resolving null and raising a TypeError.
    $content->setDefaultPresettable();

    expect($content->getRelation('presettable'))->toBeInstanceOf(Presettable::class);
});

it('resolves the entity through the default presettable when none is set', function (): void {
    $content = new Content;

    expect($content->entity)->toBeInstanceOf(Entity::class)
        ->and($content->entity->type)->toBe(EntityType::Contents);
});
