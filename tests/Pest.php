<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->in('Feature')
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a test content with all required relationships.
 */
function createTestContent(array $attributes = []): Modules\Cms\Models\Content
{
    $content = Modules\Cms\Models\Content::factory()->create($attributes);

    // Create related entities if not provided
    if (! isset($attributes['category_id'])) {
        $category = Modules\Cms\Models\Category::factory()->create();
        $content->categories()->attach($category);
    }

    if (! isset($attributes['author_id'])) {
        $author = Modules\Cms\Models\Author::factory()->create();
        $content->authors()->attach($author);
    }

    return $content;
}

/**
 * Create a test location with coordinates.
 */
function createTestLocation(array $attributes = []): Modules\Cms\Models\Location
{
    return Modules\Cms\Models\Location::factory()->create(array_merge([
        'latitude' => 45.4642,
        'longitude' => 9.1900,
        'address' => 'Milan, Italy',
    ], $attributes));
}

/**
 * Assert that a content has the expected relationships.
 */
function expectContentRelationships($content, array $relationships): void
{
    foreach ($relationships as $relation => $expectedCount) {
        expect($content->{$relation}()->count())->toBe($expectedCount);
    }
}

/**
 * Assert that a model has the expected attributes.
 */
function expectModelAttributes($model, array $attributes): void
{
    foreach ($attributes as $key => $value) {
        expect($model->{$key})->toBe($value);
    }
}

/**
 * Assert that a model exists in database with given attributes.
 */
function assertModelExists(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeTrue();
}

/**
 * Assert that a model does not exist in database with given attributes.
 */
function assertModelNotExists(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeFalse();
}
