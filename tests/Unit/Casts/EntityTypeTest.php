<?php

declare(strict_types=1);

use Modules\Cms\Casts\EntityType;

it('lists all case values', function (): void {
    $values = EntityType::values();

    expect($values)->toBeArray()
        ->and($values)->toHaveCount(3)
        ->and($values)->toContain('contents', 'contributors', 'categories');
});

it('validates known values', function (): void {
    expect(EntityType::isValid('contents'))->toBeTrue();
    expect(EntityType::isValid('unknown'))->toBeFalse();
});

it('builds laravel validation rule string', function (): void {
    $rule = EntityType::validationRule();

    expect($rule)->toStartWith('in:')
        ->and($rule)->toContain('contents')
        ->and($rule)->toContain('contributors')
        ->and($rule)->toContain('categories');
});
