# CMS import command retrieval notes

## Command ownership

- Runnable command: `cms:import`.
- Common parent: `Modules\Core\Console\AbstractImportCommand`.
- CMS marker: `Modules\CMS\Import\Contracts\BulkImporterInterface`.
- CMS resolver: `CmsBulkImporterResolver`.
- External discovery adapter: `SiblingImportersDiscovery`.
- Core intentionally exposes no `core:import` command.

The command name selects CMS as the destination. `--importer` selects the source adapter. Core never infers the destination entity or mapping.

## Options

`--importer`, `--bootstrap`, repeatable `--arg`, `--dry-run`, `--limit`, and `--no-search` are inherited from Core. Concrete CMS commands declare `$name`, not `$signature`.

## Compatibility and boundaries

The CMS marker extends Core's neutral `import(): int` contract, preserving existing Naxos importer namespaces. CMS still owns content DTOs, mapping contracts, `ImportPipeline`, upserters, preset provisioning, reference resolution, and post-processing. External packages own source clients, credentials, readers, normalization, and source-specific mappings.

Dry-run uses the connection returned by the optional connection-aware importer contract, falling back to the default connection. It rolls back only writes on that connection. The importer must suppress files, queues, HTTP calls, other connections, and all other external side effects.

Batch import and ongoing synchronization are separate capabilities. Synchronization requires remote identity, cursors, idempotency, conflict rules, retries, scheduling, locking, and observability.
