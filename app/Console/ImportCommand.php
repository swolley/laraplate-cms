<?php

declare(strict_types=1);

namespace Modules\CMS\Console;

use Illuminate\Console\Command;
use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Import\Support\BulkImportRunner;
use Modules\CMS\Import\Support\SiblingImportersDiscovery;
use Override;
use Throwable;

/**
 * Generic bulk import entry point.
 *
 * The actual import logic lives in an external plugin that implements
 * {@see BulkImporterInterface}. The plugin is loaded at runtime via its own
 * Composer autoloader (--bootstrap) and resolved from the container by FQCN
 * (--importer), receiving --arg key=value pairs plus the generic dry-run/limit
 * flags as named constructor parameters. This command is source-agnostic.
 *
 * When neither --bootstrap nor --importer is provided and a sibling
 * laraplate-importers project is present, the command may interactively offer
 * to load that project's autoloader and pick a BulkImporterInterface class.
 */
final class ImportCommand extends Command
{
    private const string SKIP_IMPORTER = '(skip)';

    #[Override]
    protected $signature = 'cms:import
                            {--importer= : Fully-qualified class name implementing BulkImporterInterface}
                            {--bootstrap= : Path to an external Composer autoloader to require before resolving the importer}
                            {--arg=* : Importer argument as key=value (repeatable)}
                            {--dry-run : Run inside a transaction and roll back without persisting}
                            {--limit= : Maximum number of records to import (honoured by the importer)}
                            {--no-search : Disable search engine (Scout) indexing for the duration of the import}';

    #[Override]
    protected $description = 'Run a bulk content import through an external importer plugin';

    public function handle(BulkImportRunner $runner, SiblingImportersDiscovery $discovery): int
    {
        $this->maybePromptSiblingImporters($discovery);

        $importer_class = mb_trim((string) $this->option('importer'));

        if ($importer_class === '') {
            $this->error('The --importer option is required (FQCN implementing BulkImporterInterface).');

            return self::FAILURE;
        }

        if (! $this->loadBootstrap()) {
            return self::FAILURE;
        }

        if (! class_exists($importer_class)) {
            $this->error("Importer class not found: {$importer_class}. Did you pass the correct --bootstrap autoloader?");

            return self::FAILURE;
        }

        $dry_run = (bool) $this->option('dry-run');
        $limit = $this->resolveLimit();

        $parameters = $this->parseArgs();
        $parameters['dryRun'] = $dry_run;
        $parameters['limit'] = $limit;

        try {
            $importer = $this->laravel->make($importer_class, $parameters);
        } catch (Throwable $exception) {
            $this->error("Unable to resolve importer [{$importer_class}]: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! $importer instanceof BulkImporterInterface) {
            $this->error("Importer [{$importer_class}] must implement " . BulkImporterInterface::class . '.');

            return self::FAILURE;
        }

        if ($dry_run) {
            $this->warn('Dry-run enabled: changes will be rolled back and side effects skipped.');
        }

        // Indexing during a bulk import is undesirable: it slows the run and, in
        // dry-run, would push records that are about to be rolled back. Forcing
        // the Scout driver to "null" disables it globally without needing to know
        // which models are Searchable.
        if ((bool) $this->option('no-search') || $dry_run) {
            config(['scout.driver' => 'null']);
            $this->warn('Search indexing disabled for this import.');
        }

        $imported = $runner->run($dry_run, static fn (): int => $importer->import());

        $this->info("Imported {$imported} record(s)" . ($dry_run ? ' (dry-run, rolled back).' : '.'));

        return self::SUCCESS;
    }

    private function maybePromptSiblingImporters(SiblingImportersDiscovery $discovery): void
    {
        if ($this->optionValueIsPresent('importer') || $this->optionValueIsPresent('bootstrap')) {
            return;
        }

        $root = $discovery->root();

        if ($root === null) {
            return;
        }

        if (! $this->confirm(
            'Found laraplate-importers at ' . $root . '. Load its Composer autoloader?',
            false,
        )) {
            return;
        }

        $autoload = $discovery->autoloadPath($root);

        if ($autoload === null) {
            $this->error('laraplate-importers vendor/autoload.php not found. Run composer install in that project first.');

            return;
        }

        require_once $autoload;

        $importers = $discovery->discoverImplementations($root);

        if ($importers === []) {
            $this->warn('No BulkImporterInterface implementations found under laraplate-importers/src.');

            return;
        }

        $choices = [self::SKIP_IMPORTER, ...$importers];

        $selected = $this->choice(
            'Select an importer (optional)',
            $choices,
            0,
        );

        if ($selected === self::SKIP_IMPORTER) {
            return;
        }

        $this->input->setOption('bootstrap', $autoload);
        $this->input->setOption('importer', $selected);
    }

    private function optionValueIsPresent(string $name): bool
    {
        $value = $this->option($name);

        return is_string($value) && mb_trim($value) !== '';
    }

    private function loadBootstrap(): bool
    {
        $bootstrap = $this->option('bootstrap');

        if (! is_string($bootstrap) || $bootstrap === '') {
            return true;
        }

        if (! is_file($bootstrap)) {
            $this->error("Bootstrap autoloader not found: {$bootstrap}");

            return false;
        }

        require_once $bootstrap;

        return true;
    }

    private function resolveLimit(): ?int
    {
        $limit = $this->option('limit');

        if ($limit === null || $limit === '') {
            return null;
        }

        return max(0, (int) $limit);
    }

    /**
     * @return array<string, string>
     */
    private function parseArgs(): array
    {
        $parsed = [];

        foreach ((array) $this->option('arg') as $pair) {
            if (! is_string($pair) || ! str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $key = mb_trim($key);

            if ($key === '') {
                continue;
            }

            $parsed[$key] = $value;
        }

        return $parsed;
    }
}
