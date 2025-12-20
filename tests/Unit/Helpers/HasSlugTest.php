<?php

declare(strict_types=1);

use Modules\Cms\Helpers\HasSlug;

it('trait can be used', static function (): void {
    $trait = new class extends Illuminate\Database\Eloquent\Model
    {
        use HasSlug;

        protected $table = 'test_table';
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugFields'))->toBeTrue();
});

it('trait has required methods', static function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugFields'))->toBeTrue();
});

it('can get slug fields', static function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    $fields = $trait->slugFields();

    expect($fields)->toBeArray();
    expect($fields)->toContain('name');
});

it('trait methods are callable', static function (): void {
    $trait = new class
    {
        use HasSlug;
    };

    expect(fn () => $trait->slugFields())->not->toThrow(Throwable::class);
});

it('trait can be used in different classes', static function (): void {
    $class1 = new class
    {
        use HasSlug;
    };

    $class2 = new class
    {
        use HasSlug;
    };

    expect(method_exists($class1, 'generateSlug'))->toBeTrue();
    expect(method_exists($class2, 'generateSlug'))->toBeTrue();
});

it('trait can be extended', static function (): void {
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

    expect(method_exists($baseClass, 'generateSlug'))->toBeTrue();
    expect(method_exists($extendedClass, 'generateSlug'))->toBeTrue();
    expect(method_exists($extendedClass, 'customMethod'))->toBeTrue();
});

it('trait has proper structure', static function (): void {
    $trait = new class extends Illuminate\Database\Eloquent\Model
    {
        use HasSlug;

        protected $table = 'test_table';
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugFields'))->toBeTrue();
});

it('trait methods are accessible', static function (): void {
    $trait = new class extends Illuminate\Database\Eloquent\Model
    {
        use HasSlug;

        protected $table = 'test_table';
    };

    expect(method_exists($trait, 'generateSlug'))->toBeTrue();
    expect(method_exists($trait, 'slug'))->toBeTrue();
    expect(method_exists($trait, 'slugFields'))->toBeTrue();
});

it('trait can be used in different scenarios', static function (): void {
    $scenario1 = new class
    {
        use HasSlug;
    };

    $scenario2 = new class
    {
        use HasSlug;
    };

    expect(method_exists($scenario1, 'generateSlug'))->toBeTrue();
    expect(method_exists($scenario2, 'generateSlug'))->toBeTrue();
});
