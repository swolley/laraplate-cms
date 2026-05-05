<?php

declare(strict_types=1);

use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('uses taxonomy_id on categorizables pivot from content side', function (): void {
    $relation = (new Content)->categories();

    expect($relation->getTable())->toBe('categorizables')
        ->and($relation->getForeignPivotKeyName())->toBe('content_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('taxonomy_id');
});

it('uses taxonomy_id on categorizables pivot from category side', function (): void {
    $relation = (new Category)->contents();

    expect($relation->getTable())->toBe('categorizables')
        ->and($relation->getForeignPivotKeyName())->toBe('taxonomy_id')
        ->and($relation->getRelatedPivotKeyName())->toBe('content_id');
});
