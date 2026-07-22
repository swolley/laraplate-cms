<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Import\Contracts\ConnectionAwareBulkImporterInterface;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Import\Contracts\BulkImporterInterface as CoreBulkImporterInterface;
use Modules\Core\Import\Contracts\ConnectionAwareBulkImporterInterface as CoreConnectionAwareBulkImporterInterface;
use Symfony\Component\Console\Command\Command;

uses(TestCase::class);

it('inherits the shared import options under the CMS command identity', function (): void {
    $command = artisanCommand('cms:import');
    $definition = $command->getDefinition();

    expect($command->getName())->toBe('cms:import')
        ->and($command->getDescription())->toContain('<fg=cyan>(📰 Modules\\CMS)</fg=cyan>')
        ->and($definition->getArguments())->toBe([])
        ->and(array_keys($definition->getOptions()))->toContain(
            'importer',
            'bootstrap',
            'arg',
            'dry-run',
            'limit',
            'no-search',
        )
        ->and($definition->getOption('arg')->isArray())->toBeTrue();
});

it('keeps CMS importer contracts as Core-compatible module markers', function (): void {
    expect(is_subclass_of(BulkImporterInterface::class, CoreBulkImporterInterface::class))->toBeTrue()
        ->and(is_subclass_of(
            ConnectionAwareBulkImporterInterface::class,
            CoreConnectionAwareBulkImporterInterface::class,
        ))->toBeTrue();
});

function artisanCommand(string $name): Command
{
    $command = Artisan::all()[$name] ?? null;

    expect($command)->toBeInstanceOf(Command::class);

    return $command;
}
