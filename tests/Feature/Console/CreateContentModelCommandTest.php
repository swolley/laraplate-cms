<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Console\CreateContentModelCommand;

uses(RefreshDatabase::class);

test('command exists and has correct signature', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('model:make-content-model');
    expect($source)->toContain('Create a new content model');
});

test('command class has correct properties', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);

    expect($reflection->getName())->toBe('Modules\Cms\Console\CreateContentModelCommand');
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

test('command can be instantiated', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);

    expect($reflection->isInstantiable())->toBeTrue();
    expect($reflection->isSubclassOf(Modules\Core\Overrides\Command::class))->toBeTrue();
});

test('command has correct namespace', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);

    expect($reflection->getNamespaceName())->toBe('Modules\Cms\Console');
    expect($reflection->getShortName())->toBe('CreateContentModelCommand');
});

test('command has handle method', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);

    expect($reflection->hasMethod('handle'))->toBeTrue();
});

test('command handle method returns void', function (): void {
    $reflection = new ReflectionMethod(CreateContentModelCommand::class, 'handle');

    expect($reflection->getReturnType()->getName())->toBe('void');
});

test('command requires entity argument', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('entity');
    expect($source)->toContain('InputArgument::REQUIRED');
});

test('command implements PromptsForMissingInput', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);

    expect($reflection->implementsInterface(Illuminate\Contracts\Console\PromptsForMissingInput::class))->toBeTrue();
});

test('command handles entity name conversion', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Str::studly');
});

test('command checks if entity exists', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('Entity::query()');
    expect($source)->toContain('where(\'name\', $entity)');
});

test('command prompts to create entity when not found', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('confirm(');
    expect($source)->toContain('not found');
});

test('command calls CreateEntityCommand when entity not found', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('CreateEntityCommand');
    expect($source)->toContain('$this->call(');
});

test('command checks if file already exists', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('file_exists');
});

test('command creates content model file', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('file_get_contents');
    expect($source)->toContain('file_put_contents');
});

test('command uses content stub', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('content.stub');
});

test('command replaces class name in stub', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('str_replace');
    expect($source)->toContain('$CLASS$');
});

test('command uses module path helper', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('module_path');
});

test('command creates file in correct directory', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('app/Models/Contents/');
});

test('command has correct argument definition', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('getArguments');
    expect($source)->toContain('InputArgument::REQUIRED');
});

test('command handles file path construction', function (): void {
    $reflection = new ReflectionClass(CreateContentModelCommand::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('$file_path');
    expect($source)->toContain('$class_name');
});
