<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\FieldType;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Models\Field;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! Schema::hasTable(CMSTables::Contents->value)) {
        $this->markTestSkipped('CMS schema required.');
    }

    setupCMSEntities();
});

function aLinkedField(): Field
{
    $field_id = DB::table(CoreTables::Fieldables->value)->value('field_id');

    return Field::query()->findOrFail($field_id);
}

function anotherType(FieldType $current): FieldType
{
    return $current === FieldType::Text ? FieldType::Editor : FieldType::Text;
}

it('forbids changing the type once the field is linked to a preset', function (): void {
    $field = aLinkedField();
    $field->type = anotherType($field->type);

    expect(fn (): bool => $field->save())->toThrow(ValidationException::class);
});

it('forbids changing translatability once the field is linked to a preset', function (): void {
    $field = aLinkedField();
    $field->is_translatable = ! $field->is_translatable;

    expect(fn (): bool => $field->save())->toThrow(ValidationException::class);
});

it('still allows changing non-structural attributes on a linked field', function (): void {
    $field = aLinkedField();
    $field->options = (object) ['max_length' => 128];
    $field->save();

    expect($field->fresh()->options->max_length)->toBe(128);
});

it('allows changing structural attributes while the field is not linked yet', function (): void {
    $field = new Field(['name' => 'unlinked_' . uniqid(), 'type' => FieldType::Text, 'options' => (object) []]);
    $field->is_translatable = false;
    $field->save();

    $field->type = FieldType::Editor;
    $field->is_translatable = true;
    $field->save();

    $fresh = $field->fresh();

    expect($fresh->type)->toBe(FieldType::Editor)
        ->and((bool) $fresh->is_translatable)->toBeTrue();
});
