# Content extension seam

Lets another module attach a richer domain model to a CMS `Content` without CMS knowing that model,
and without the extended rows leaking into generic CMS surfaces. The extender is a **separate table**
with a `content_id` foreign key — not a subtype of `Content`. First intended consumer: a shop
`Product` that ties a `Content` (editorial body) to an ERP `Item`.

Design record: `docs/superpowers/specs/2026-09-17-cms-content-extension-seam-design.md` (decisions
C1–C18).

## How it works

- **Self-describing column.** `contents.extended_type` is a nullable morph alias (e.g.
  `shop.product`); `null` means a normal content. It is set once, by the extender's create path, and
  is excluded from versioning (it is identity, not editorial state).
- **Dedicated registry.** `Services\ContentExtenderRegistry` maps alias → extender class. It is code,
  keyed by the stable alias, populated at boot by each extender's service provider — never a table and
  never Laravel's global morph map. With no extender registered it is empty and the whole seam is a
  no-op.
- **Default hide.** The `Scopes\HidesExtendedContent` global scope adds `WHERE extended_type IS NULL`
  to every default `Content` read. Opt back in with `Content::withExtended()`, which removes **only**
  that scope (soft-delete, validity and any other global scope stay applied).
- **Batched upcast.** `Services\ContentExtensionResolver::resolve(Collection $contents)` swaps each
  extended content for its extender, re-attaching the content via `setRelation('content', …)`. One
  query per distinct alias on the page (`whereIn('content_id', …)->without('content')`), never one per
  row. An unregistered alias or a missing owner row fails loud.
- **Back-relation.** The extender's `content()` is a `BelongsTo` with `HidesExtendedContent` removed,
  so `$extender->content` always resolves.
- **Lifecycle.** The extender owns the content: its soft/force delete and restore cascade to the
  content (restore via Core's `reviveInMemory()` + `save()`). A directly deleted content is applied to
  the extender per policy — **mandatory** content cascades to delete the extender, **optional** content
  orphans it (`content_id` nulled). A shared `Services\ContentExtensionCascade` re-entrancy guard stops
  the two directions colliding.
- **Search.** One physical `contents` index. `makeAllSearchableUsing()` includes extended contents in
  the bulk import (per-save indexing already sees them). `toSearchableArray()` adds a filterable
  `extended_type` and a nested typed `extension` object from the extender's `searchableExtension()`.
  `getSearchMapping()` composes the `extension` mapping from every registered extender's
  `searchableExtensionMapping()`. No ERP/external data is indexed. A module reindex is document-scoped
  (`Content::withExtended()->where('extended_type', $alias)->searchable()`), never an index-lifecycle
  command against `contents`.

## Becoming a consumer

1. Create the extender model with a `content_id` **unique** foreign key to `contents`.
2. Implement `Contracts\ExtendsContent`, or `use Models\Concerns\ExtendsContentTrait` (which provides
   the back-relation, `$with = ['content']`, `setTempContent()`, the create-path `save()` that stamps
   `extended_type`, the lifecycle cascade, and a `contentIsMandatory()` default of `true`).
3. Register the alias at boot: `app(ContentExtenderRegistry::class)->register('shop.product', Product::class)`.
4. Create the pair through the extender: `$product->setTempContent($content); $product->save();`.
5. Decide `contentIsMandatory()` (default `true` = symmetric delete; `false` = orphan on content
   deletion — needs a nullable `content_id`).
6. Provide `searchableExtension()` (data) and `searchableExtensionMapping()` (Core `FieldType`
   schema properties) for the `extension` search section.

## Generic search excludes extended contents

Extended contents are **indexed** (for a future opt-in shop search) but kept out of the **generic**
content search. `Content` implements `Modules\Core\Contracts\ProvidesDefaultSearchFilters`, returning
`['extended_type' => null]`; `CrudService` applies it to the Scout query, and `= null` compiles to
`IS NULL`, so the engine never returns extended contents for a generic search (no short pages at
rehydration). On the database engine the hide global scope already excludes them; the filter is what
keeps the separate-index engines (Elasticsearch/Typesense) hole-free, where `extended_type` is a real
indexed field. An opt-in surface (e.g. a shop search) queries extended contents via the per-alias
`extended_type` value instead.

End-to-end ES/Typesense verification is left to the first consumer's integration (the database engine's
own `search('*')` over `Content` has a pre-existing, unrelated column-set limitation in tests).
