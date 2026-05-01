# CMS module — editorial domain and media workflows

## Purpose

`CMS` manages editorial content lifecycle for Laraplate installations: content entities, publication records, taxonomy, media, and location-aware metadata.

Important: some historically CMS-associated capabilities may now live in `Core`. Always treat current code ownership as source of truth.

## Main capabilities

### Content modeling

- Defines and manages editorial entities and content records.
- Supports template-driven structures for consistent publishing.
- Provides reusable presets for common editorial payloads.

### Taxonomy and classification

- Category/tag relationships for navigation and discovery.
- Contributor and authorship metadata where enabled.
- Location entities for geocoded or geo-filtered content use cases.

### Media handling

- Integrates media library stack for attachments and transformed assets.
- Supports async media processing patterns (queue-first for heavy conversions).
- Can coexist with project-specific front-end render pipelines.

### Filament module integration

- Registers module resources through CMS plugin wiring.
- Participates in panel navigation clusters and CMS-oriented widgets.

## How to use

1. Define entity/template model before importing large datasets.
2. Create content records and attach taxonomy/contributor/media links.
3. Use location/geocoding services only when provider constraints and quotas are understood.
4. Expose CMS outputs through API/theme routes based on project conventions.

## Internal flow (high-level)

- Resource form inputs are normalized and persisted in CMS domain models.
- Media uploads route through media library storage and conversion pipeline.
- Optional geocoding enriches location fields before final persistence.
- Query/UI layers consume taxonomy and relation metadata for filtering and navigation.

## Dependencies and boundaries

- Depends on `Core` for identity, permissions, lifecycle traits, and shared CRUD infrastructure.
- Should not duplicate cross-cutting capabilities already provided by `Core` (approvals, locking, versioning, ACL mechanics).

## Common pitfalls

- Migrating legacy content without first aligning templates/presets causes inconsistent structures.
- Treating CMS as a plain WYSIWYG system underuses available structured content model.
- Ignoring ownership shifts between Core and CMS leads to outdated documentation and incorrect extension points.

## FAQ prompts for RAG

- Which content features are still owned by CMS versus moved to Core?
- How do templates and presets differ in practical editorial workflows?
- How should taxonomy be modeled for multi-locale content?
- When should CMS media processing run through queues?
- What is the safest migration path for legacy CMS data imports?
