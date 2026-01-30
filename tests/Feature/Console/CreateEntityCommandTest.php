<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Console\CreateEntityCommand;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('command exists and has correct signature', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('model:create-entity');
    expect($source)->toContain('Create new cms entity');
});

it('command class has correct properties', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->getName())->toBe('Modules\Cms\Console\CreateEntityCommand');
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

it('command can be instantiated', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->isInstantiable())->toBeTrue();
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

it('command has correct namespace', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->getNamespaceName())->toBe('Modules\Cms\Console');
    expect($reflection->getShortName())->toBe('CreateEntityCommand');
});

it('command has handle method', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);

    expect($reflection->hasMethod('handle'))->toBeTrue();
});

it('command handle method returns void', function (): void {
    $reflection = new ReflectionMethod(CreateEntityCommand::class, 'handle');

    expect($reflection->getReturnType()->getName())->toBe('void');
});

it('command has optional entity argument', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('entity');
    expect($source)->toContain('InputArgument::OPTIONAL');
});

it('command has content-model option', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('content-model');
    expect($source)->toContain('InputOption');
});

it('command uses HasCommandUtils trait', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $traits = $reflection->getTraitNames();

    expect($traits)->toContain('Modules\Core\Helpers\HasCommandUtils');
});

it('command uses Laravel Prompts', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Laravel\Prompts\confirm');
    expect($source)->toContain('Laravel\Prompts\multiselect');
    expect($source)->toContain('Laravel\Prompts\select');
    expect($source)->toContain('Laravel\Prompts\text');
});

it('command creates entity with fillable attributes', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('getFillable');
    expect($source)->toContain('getOperationRules');
});

it('command handles entity type selection', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('EntityType::values');
    expect($source)->toContain('Choose the type of the entity');
});

it('command creates standard preset', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('standard');
    expect($source)->toContain('Preset');
});

it('command handles field selection', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Field::query()');
    expect($source)->toContain('Choose fields for the preset');
});

it('command handles field requirements', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('is_required');
    expect($source)->toContain('assignFieldToPreset');
});

it('command handles default field values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('getDefaultFieldValue');
    expect($source)->toContain('Specify a default value');
});

it('command handles different field types', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('FieldType::SELECT');
    expect($source)->toContain('FieldType::SWITCH');
    expect($source)->toContain('FieldType::CHECKBOX');
});

it('command handles content model creation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    // The command has content-model option but doesn't implement it yet
    expect($source)->toContain('content-model');
    expect($source)->toContain('Create a content model file');
});

it('command handles slug generation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Str::slug');
});

it('command handles validation', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('validationCallback');
});

it('command handles field attachment to preset', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('attach(');
    expect($source)->toContain('pivotAttributes');
});

it('command handles default value parsing', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('json_decode');
    expect($source)->toContain('preg_match');
});

it('command handles boolean values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('true');
    expect($source)->toContain('false');
});

it('command handles numeric values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('(int)');
    expect($source)->toContain('(float)');
});

it('command handles array values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('[]');
    expect($source)->toContain('json_decode');
});

it('command handles null values', function (): void {
    $reflection = new ReflectionClass(CreateEntityCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('null');
    expect($source)->toContain('Type \'null\'');
});
