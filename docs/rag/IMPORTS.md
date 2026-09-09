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

## Which entity an import writes into

An import DTO names an entity **type** — `contents`, `categories`, `contributors` — never an entity. Singular aliases (post, event, content, multimedia, category, section, folder, contributor) normalize to those three types. Which entity of that type a project uses is the project's own naming, so `EntityPresetResolver` looks it up by type and picks, in order:

1. the only entity of that type, when the project has one;
2. the entity the importer named through the DTO's `preferredEntityName`;
3. the entity flagged as the default for that type.

The import creates no entity. A type with no entity stops the run, and so does an ambiguous type where nothing is named or default: the message lists the candidates. `ImportPresetProvisioner` resolves the same way and provisions only presets, their fields, and the presettable version, which do belong to the import.

An importer supplies `preferredEntityName` when the destination project told it which entity to use. The Naxos importer reads it per type from `--arg entityContents=`, `--arg entityCategories=` and `--arg entityContributors=`, and leaves it null otherwise.

## Provenance recorded per record

Every upserted record gets a row in `core_record_origins`, written through `ExternalReferenceLocator::register()`. Identity is the pair source key plus external id, which is what makes a repeated import update instead of duplicate. Alongside it the row carries four descriptive fields, all optional and all supplied by the importer through the DTO:

- `source_label` and `url`, from `originLabel` and `originUrl`. Every import DTO declares them: contents, categories, tags, contributors and locations.
- `fingerprint`, a SHA-256 over the mapped payload, computed by `Modules\Core\Import\Support\ImportFingerprint` from the DTO itself. Key order never affects it, so only a changed value changes the hash.
- `source_updated_at`, taken from the DTO's `updatedAt`, which is the source system's own modification timestamp. A string the source sends that cannot be parsed leaves the column empty rather than failing the row.

An importer that has no url for a taxonomy leaves `originUrl` null; the origin row is still written and the record stays identifiable.

## Compatibility and boundaries

The CMS marker extends Core's neutral `import(?OutputInterface $output = null): int` contract, preserving existing Naxos importer namespaces. When the console output is passed (from `cms:import`), CMS forwards it to `ImportPipeline` / `ImportProgressLogger` for per-content progress lines; omitting it keeps pipeline-only runs quiet. CMS still owns content DTOs, mapping contracts, `ImportPipeline`, upserters, preset provisioning, reference resolution, and post-processing. External packages own source clients, credentials, readers, normalization, and source-specific mappings.

Dry-run uses the connection returned by the optional connection-aware importer contract, falling back to the default connection. It rolls back only writes on that connection. The importer must suppress files, queues, HTTP calls, other connections, and all other external side effects.

Batch import and ongoing synchronization are separate capabilities. Synchronization requires remote identity, cursors, idempotency, conflict rules, retries, scheduling, locking, and observability.
