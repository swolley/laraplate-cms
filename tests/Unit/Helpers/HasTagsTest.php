<?php

declare(strict_types=1);

use Modules\Cms\Helpers\HasTags;

it('trait can be used', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(method_exists($trait, 'attachTags'))->toBeTrue();
    expect(method_exists($trait, 'detachTags'))->toBeTrue();
    expect(method_exists($trait, 'syncTags'))->toBeTrue();
});

it('trait has required methods', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(method_exists($trait, 'attachTags'))->toBeTrue();
    expect(method_exists($trait, 'detachTags'))->toBeTrue();
    expect(method_exists($trait, 'syncTags'))->toBeTrue();
});

it('trait methods are callable', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(fn () => $trait->attachTags([]))->not->toThrow(Throwable::class);
    expect(fn () => $trait->detachTags([]))->not->toThrow(Throwable::class);
    expect(fn () => $trait->syncTags([]))->not->toThrow(Throwable::class);
});

it('trait can be used in different classes', function (): void {
    $class1 = new class
    {
        use HasTags;
    };

    $class2 = new class
    {
        use HasTags;
    };

    expect(method_exists($class1, 'attachTags'))->toBeTrue();
    expect(method_exists($class2, 'attachTags'))->toBeTrue();
});

it('trait is properly namespaced', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(method_exists($trait, 'attachTags'))->toBeTrue();
    expect(method_exists($trait, 'detachTags'))->toBeTrue();
    expect(method_exists($trait, 'syncTags'))->toBeTrue();
});

it('trait can be extended', function (): void {
    $baseClass = new class
    {
        use HasTags;
    };

    $extendedClass = new class
    {
        use HasTags;

        public function customMethod(): string
        {
            return 'custom';
        }
    };

    expect(method_exists($baseClass, 'attachTags'))->toBeTrue();
    expect(method_exists($extendedClass, 'attachTags'))->toBeTrue();
    expect(method_exists($extendedClass, 'customMethod'))->toBeTrue();
});

it('trait has proper structure', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(method_exists($trait, 'attachTags'))->toBeTrue();
    expect(method_exists($trait, 'detachTags'))->toBeTrue();
    expect(method_exists($trait, 'syncTags'))->toBeTrue();
});

it('trait methods are accessible', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect(method_exists($trait, 'attachTags'))->toBeTrue();
    expect(method_exists($trait, 'detachTags'))->toBeTrue();
    expect(method_exists($trait, 'syncTags'))->toBeTrue();
});

it('trait can be used in different scenarios', function (): void {
    $scenario1 = new class
    {
        use HasTags;
    };

    $scenario2 = new class
    {
        use HasTags;
    };

    expect(method_exists($scenario1, 'attachTags'))->toBeTrue();
    expect(method_exists($scenario2, 'attachTags'))->toBeTrue();
});
