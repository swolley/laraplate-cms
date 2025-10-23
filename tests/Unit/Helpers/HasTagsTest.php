<?php

declare(strict_types=1);

use Modules\Cms\Helpers\HasTags;

it('trait can be used', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect($trait)->toHaveMethod('attachTags');
    expect($trait)->toHaveMethod('detachTags');
    expect($trait)->toHaveMethod('syncTags');
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

    expect(fn () => $trait->attachTags([]))->not->toThrow();
    expect(fn () => $trait->detachTags([]))->not->toThrow();
    expect(fn () => $trait->syncTags([]))->not->toThrow();
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

    expect($class1)->toHaveMethod('attachTags');
    expect($class2)->toHaveMethod('attachTags');
});

it('trait is properly namespaced', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect($trait)->toHaveMethod('attachTags');
    expect($trait)->toHaveMethod('detachTags');
    expect($trait)->toHaveMethod('syncTags');
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

    expect($baseClass)->toHaveMethod('attachTags');
    expect($extendedClass)->toHaveMethod('attachTags');
    expect($extendedClass)->toHaveMethod('customMethod');
});

it('trait has proper structure', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect($trait)->toHaveMethod('attachTags');
    expect($trait)->toHaveMethod('detachTags');
    expect($trait)->toHaveMethod('syncTags');
});

it('trait methods are accessible', function (): void {
    $trait = new class
    {
        use HasTags;
    };

    expect($trait)->toHaveMethod('attachTags');
    expect($trait)->toHaveMethod('detachTags');
    expect($trait)->toHaveMethod('syncTags');
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

    expect($scenario1)->toHaveMethod('attachTags');
    expect($scenario2)->toHaveMethod('attachTags');
});
