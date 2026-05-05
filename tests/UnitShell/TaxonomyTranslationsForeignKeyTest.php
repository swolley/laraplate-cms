<?php

declare(strict_types=1);

use Modules\CMS\Models\Category;

uses(Tests\TestCase::class);

it('binds translations to taxonomies_translations.taxonomy_id', function (): void {
    $category = new Category;

    expect($category->translations()->getForeignKeyName())->toBe('taxonomy_id')
        ->and($category->translation()->getForeignKeyName())->toBe('taxonomy_id');
});
