# CMS bulk imports

CMS owns the runnable `cms:import` command and the destination content pipeline. Core owns the shared command parser, plugin discovery primitives, importer resolution, and transactional runner. There is no runnable `core:import` command.

## Run an external importer

Pass an importer class and, when it is not already autoloaded, its Composer bootstrap:

```bash
php artisan cms:import \
  --bootstrap='/absolute/path/to/laraplate-importers/vendor/autoload.php' \
  --importer='Naxos\Importers\NaxosApiImporter' \
  --arg='endpoint=https://example.invalid/api' \
  --arg='token=secret' \
  --limit=100 \
  --dry-run
```

When the sibling `laraplate-importers` checkout is available, running `php artisan cms:import` without `--bootstrap` or `--importer` offers interactive plugin discovery.

## Common options

| Option | Meaning |
|---|---|
| `--importer=` | Fully qualified importer class implementing the CMS marker contract |
| `--bootstrap=` | Absolute path to an external Composer autoloader |
| `--arg=*` | Repeatable importer constructor argument in `key=value` form |
| `--dry-run` | Roll back writes made on the importer-declared connection, or the default connection |
| `--limit=` | Non-negative limit passed to the importer |
| `--no-search` | Disable Scout indexing for the import process |

The command injects `dryRun` and `limit` as named constructor parameters. Source-specific arguments are intentionally not part of the common command contract.

## Dry-run boundary

An importer may implement `ConnectionAwareBulkImporterInterface` to select the transaction connection. Otherwise the current default connection is used. Dry-run rolls back database writes made on that one connection only.

It does not reverse writes on additional connections, files, object storage, queues, HTTP calls, or provider-side effects. Importers must inspect `dryRun` and suppress every non-transactional side effect. Scout is disabled automatically during dry-run.

## Develop a CMS importer

External CMS importers continue to implement `Modules\CMS\Import\Contracts\BulkImporterInterface`. This compatibility marker extends Core's neutral contract and requires:

```php
public function import(): int;
```

Return the number of imported root records. Use CMS DTOs, `ImportPipeline`, and domain services for destination writes. Do not move source credentials, source clients, or source-specific mappings into CMS or Core.

The command implementation is deliberately thin: `Modules\CMS\Console\ImportCommand` declares `cms:import`, adds the colored CMS suffix, and injects the CMS resolver and plugin discovery adapter into Core's `AbstractImportCommand`. CMS retains `BulkImportRunner` as a compatibility adapter because existing Naxos importers call its static `limitReached()` helper.

`Naxos\Importers\NaxosApiImporter` and `Naxos\Importers\NaxosSqlImporter` remain compatible with the retained CMS namespace.

## Import is not synchronization

`cms:import` is a bounded, operator-triggered batch operation. Continuous synchronization additionally needs remote identity, cursors, direction, conflict policy, retries, locking, scheduling, and observability. Those concerns must be designed as a separate integration layer, even when it reuses CMS import mapping and destination services.
