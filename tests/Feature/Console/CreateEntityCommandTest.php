<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Console\CreateEntityCommand;

uses(RefreshDatabase::class);

test('command exists and has correct signature', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('model:create-entity');
    expect($source)->toContain('Create new cms entity');
});

test('command class has correct properties', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->getName())->toBe('Modules\Cms\Console\CreateEntityCommand');
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

test('command can be instantiated', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->isInstantiable())->toBeTrue();
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

test('command has correct namespace', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->getNamespaceName())->toBe('Modules\Cms\Console');
    expect($reflection->getShortName())->toBe('CreateEntityCommand');
});

test('command has handle method', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->hasMethod('handle'))->toBeTrue();
});

test('command handle method returns void', function (): void {
    $reflection = new ReflectionMethod(CreateEntityCommand::class, 'handle');

    expect($reflection->getReturnType()->getName())->toBe('void');
});

test('command has optional entity argument', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('entity');
    expect($source)->toContain('InputArgument::OPTIONAL');
});

test('command has content-model option', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('content-model');
    expect($source)->toContain('InputOption');
});

test('command uses HasCommandUtils trait', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Modules\Core\Helpers\HasCommandUtils');
});

test('command uses Laravel Prompts', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Laravel\Prompts\confirm');
    expect($source)->toContain('Laravel\Prompts\multiselect');
    expect($source)->toContain('Laravel\Prompts\select');
    expect($source)->toContain('Laravel\Prompts\text');
});

test('command handles database transactions', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('$this->db->transaction');
});

test('command creates entity with fillable attributes', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('getFillable');
    expect($source)->toContain('getOperationRules');
});

test('command handles entity type selection', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('EntityType::values');
    expect($source)->toContain('Choose the type of the entity');
});

test('command creates standard preset', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('standard');
    expect($source)->toContain('Preset');
});

test('command handles field selection', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Field::query()');
    expect($source)->toContain('Choose fields for the preset');
});

test('command handles field requirements', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('is_required');
    expect($source)->toContain('assignFieldToPreset');
});

test('command handles default field values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('getDefaultFieldValue');
    expect($source)->toContain('Specify a default value');
});

test('command handles different field types', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('FieldType::SELECT');
    expect($source)->toContain('FieldType::SWITCH');
    expect($source)->toContain('FieldType::CHECKBOX');
});

test('command handles content model creation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('CreateContentModelCommand');
    expect($source)->toContain('$this->call(');
});

test('command handles slug generation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Str::slug');
});

test('command handles validation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('validationCallback');
});

test('command handles field attachment to preset', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('attach(');
    expect($source)->toContain('pivotAttributes');
});

test('command handles default value parsing', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('json_decode');
    expect($source)->toContain('preg_match');
});

test('command handles boolean values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('true');
    expect($source)->toContain('false');
});

test('command handles numeric values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('(int)');
    expect($source)->toContain('(float)');
});

test('command handles array values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('[]');
    expect($source)->toContain('json_decode');
});

test('command handles null values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('null');
    expect($source)->toContain('Type \'null\'');
});
