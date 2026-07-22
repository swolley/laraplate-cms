<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Canonical import identity resolution for every CMS upserter.
 *
 * Resolution order (same for all entity types):
 * 1. In-memory map for the current import run ({@see ImportIdMap})
 * 2. Persistent registry ({@see ExternalReferenceLocator} / core_record_origins)
 * 3. Deterministic import slug in translation tables (when configured)
 */
final class ImportReferenceResolver
{
    public function __construct(
        private readonly ImportIdMap $id_map,
        private readonly ExternalReferenceLocator $locator,
    ) {}

    /**
     * @param  class-string<Model>  $referable_class
     */
    public function resolve(
        string $map_entity,
        string $referable_class,
        int $external_id,
        string $source_type,
        ?ImportConnectionContext $context = null,
    ): ?int {
        $referable = $context?->model($referable_class) ?? new $referable_class;

        return $this->id_map->resolve(
            $map_entity,
            $external_id,
            $context?->connectionName(),
            $context instanceof ImportConnectionContext ? $source_type : null,
        ) ?? $this->locator->findImportedRecordId(
            $referable,
            $external_id,
            $source_type,
            context: $context,
        );
    }

    public function remember(
        string $map_entity,
        int $external_id,
        int $local_id,
        string $source_type = '',
        ?ImportConnectionContext $context = null,
    ): void {
        $this->id_map->remember(
            $map_entity,
            $external_id,
            $local_id,
            $context?->connectionName(),
            $source_type,
        );
    }

    /**
     * @param  list<int>  $external_ids
     * @return list<int>
     */
    public function resolveMany(
        string $map_entity,
        string $referable_class,
        array $external_ids,
        string $source_type,
        ?ImportConnectionContext $context = null,
    ): array {
        $resolved = [];

        foreach ($external_ids as $external_id) {
            $local_id = $this->resolve($map_entity, $referable_class, $external_id, $source_type, $context);

            if ($local_id !== null) {
                $resolved[] = $local_id;
            }
        }

        return array_values(array_unique($resolved));
    }
}
