<?php

declare(strict_types=1);

use Modules\Cms\Helpers\HasSlug;

it('trait can be used', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect($trait)->toHaveMethod('generateSlug');
    expect($trait)->toHaveMethod('getSlug');
    expect($trait)->toHaveMethod('slugFields');
});

it('trait has required methods', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugFields'))->toBeTrue();
});

it('can get slug fields', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    $fields = $trait->slugFields();

    expect($fields)->toBeArray();
    expect($fields)->toContain('name');
});

it('trait methods are callable', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect(fn () => $trait->slugFields())->not->toThrow(Throwable::class);
});

it('trait can be used in different classes', function (): void {
    $class1 = new class
    {
        use HasSlug;
    };

    $class2 = new class
    {
        use HasSlug;
    };

    expect($class1)->toHaveMethod('generateSlug');
    expect($class2)->toHaveMethod('generateSlug');
});

it('trait can be extended', function (): void {
    $baseClass = new class
    {
        use HasSlug;
    };

    $extendedClass = new class
    {
        use HasSlug;

        public function customMethod(): string
        {
            return 'custom';
        }
    };

    expect($baseClass)->toHaveMethod('generateSlug');
    expect($extendedClass)->toHaveMethod('generateSlug');
    expect($extendedClass)->toHaveMethod('customMethod');
});

it('trait has proper structure', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect($trait)->toHaveMethod('generateSlug');
    expect($trait)->toHaveMethod('getSlug');
    expect($trait)->toHaveMethod('slugFields');
});

it('trait methods are accessible', function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect($trait)->toHaveMethod('generateSlug');
    expect($trait)->toHaveMethod('getSlug');
    expect($trait)->toHaveMethod('slugFields');
});

it('trait can be used in different scenarios', function (): void {
    $scenario1 = new class
    {
        use HasSlug;
    };

    $scenario2 = new class
    {
        use HasSlug;
    };

    expect($scenario1)->toHaveMethod('generateSlug');
    expect($scenario2)->toHaveMethod('generateSlug');
});
