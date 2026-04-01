<?php

declare(strict_types=1);

use Modules\Cms\Casts\FieldType;

it('maps field types to validation rule fragments', function (): void {
    expect(FieldType::TEXT->getRule())->toBe('string');
    expect(FieldType::TEXTAREA->getRule())->toBe('string');
    expect(FieldType::SWITCH->getRule())->toBe('boolean');
    expect(FieldType::CHECKBOX->getRule())->toBe('array');
    expect(FieldType::DATETIME->getRule())->toBe('date');
    expect(FieldType::NUMBER->getRule())->toBe('number');
    expect(FieldType::OBJECT->getRule())->toBe('json');
    expect(FieldType::EDITOR->getRule())->toBe('json');
    expect(FieldType::ARRAY->getRule())->toBe('array');
    expect(FieldType::EMAIL->getRule())->toBe('email');
    expect(FieldType::URL->getRule())->toBe('url');
});

it('returns empty rule for select-like types using default branch', function (): void {
    expect(FieldType::SELECT->getRule())->toBe('');
    expect(FieldType::RADIO->getRule())->toBe('');
});

it('detects textual field types', function (): void {
    expect(FieldType::TEXT->isTextual())->toBeTrue();
    expect(FieldType::TEXTAREA->isTextual())->toBeTrue();
    expect(FieldType::EDITOR->isTextual())->toBeTrue();
    expect(FieldType::NUMBER->isTextual())->toBeFalse();
    expect(FieldType::SWITCH->isTextual())->toBeFalse();
});
