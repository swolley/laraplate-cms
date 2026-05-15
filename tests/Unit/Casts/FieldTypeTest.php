<?php

declare(strict_types=1);

use Modules\Core\Casts\FieldType;

it('maps field types to validation rule fragments', function (): void {
    expect(FieldType::Text->getRule())->toBe('string');
    expect(FieldType::Textarea->getRule())->toBe('string');
    expect(FieldType::Switch->getRule())->toBe('boolean');
    expect(FieldType::Checkbox->getRule())->toBe('array');
    expect(FieldType::Datetime->getRule())->toBe('date');
    expect(FieldType::Number->getRule())->toBe('number');
    expect(FieldType::Object->getRule())->toBe('json');
    expect(FieldType::Editor->getRule())->toBe('json');
    expect(FieldType::Array->getRule())->toBe('array');
    expect(FieldType::Email->getRule())->toBe('email');
    expect(FieldType::Url->getRule())->toBe('url');
});

it('returns empty rule for select-like types using default branch', function (): void {
    expect(FieldType::Select->getRule())->toBe('');
    expect(FieldType::Radio->getRule())->toBe('');
});

it('detects textual field types', function (): void {
    expect(FieldType::Text->isTextual())->toBeTrue();
    expect(FieldType::Textarea->isTextual())->toBeTrue();
    expect(FieldType::Editor->isTextual())->toBeTrue();
    expect(FieldType::Number->isTextual())->toBeFalse();
    expect(FieldType::Switch->isTextual())->toBeFalse();
});
