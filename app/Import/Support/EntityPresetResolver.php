<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable as CmsPresettable;
use Modules\CMS\Models\Preset;
use RuntimeException;

/**
 * Resolves the entity an import writes into, and the preset version it uses.
 *
 * An import DTO names a *type* (contents, categories, contributors), never an
 * entity: which entity of that type a project uses is the project's own naming.
 * The entity is therefore looked up by type and picked in this order:
 *
 * 1. the only entity of that type, when the project has one;
 * 2. the entity the importer asked for by name, when it exists;
 * 3. the entity marked as the default for that type.
 *
 * Nothing is created here. A type with no entity is a setup mistake, and an
 * ambiguous one is a decision the import cannot make on its own: both stop the
 * run with a message naming the candidates.
 */
final class EntityPresetResolver
{
    /**
     * @var array<string, int>
     */
    private array $entity_ids = [];

    /**
     * @var array<string, int>
     */
    private array $presettable_ids = [];

    public function entityId(string $entityName, ?ImportConnectionContext $context = null, ?string $preferredEntityName = null): int
    {
        $entity_type = ImportEntityNames::normalize($entityName);
        $cache_key = $entity_type . '|' . ($preferredEntityName ?? '');

        if (isset($this->entity_ids[$cache_key])) {
            return $this->entity_ids[$cache_key];
        }

        $context ??= new ImportConnectionContext(new Entity);

        $candidates = $context->model(Entity::class)->newQuery()
            ->where('type', $entity_type)
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            throw new RuntimeException(
                "No CMS entity exists for type [{$entity_type}]. Create one before importing.",
            );
        }

        $entity = $candidates->count() === 1
            ? $candidates->first()
            : ($this->entityNamed($candidates, $preferredEntityName) ?? $this->defaultEntity($candidates));

        if ($entity === null) {
            $names = $candidates->pluck('name')->implode(', ');

            throw new RuntimeException(
                "Cannot tell which CMS entity of type [{$entity_type}] to import into. "
                . "Candidates: {$names}. Name one from the importer, or mark one as the default.",
            );
        }

        $this->entity_ids[$cache_key] = (int) $entity->getKey();

        return (int) $entity->getKey();
    }

    public function presettableId(string $entityName, string $presetName, ?ImportConnectionContext $context = null, ?string $preferredEntityName = null): int
    {
        $entityName = ImportEntityNames::normalize($entityName);

        $cache_key = "{$entityName}:{$presetName}:" . ($preferredEntityName ?? '');

        if (isset($this->presettable_ids[$cache_key])) {
            return $this->presettable_ids[$cache_key];
        }

        $context ??= new ImportConnectionContext(new Entity);
        $entity_id = $this->entityId($entityName, $context, $preferredEntityName);

        $preset = $context->model(Preset::class)->newQuery()
            ->where('entity_id', $entity_id)
            ->where('name', $presetName)
            ->first();

        if ($preset === null) {
            throw new RuntimeException("CMS preset not found: {$entityName}/{$presetName}");
        }

        $presettable = $context->model(CmsPresettable::class)->newQuery()
            ->where('preset_id', $preset->id)
            ->where('entity_id', $entity_id)
            ->whereNull('deleted_at')
            ->latest('version')
            ->first();

        if (! $presettable instanceof CmsPresettable) {
            throw new RuntimeException("No active presettable for preset: {$entityName}/{$presetName}");
        }

        $this->presettable_ids[$cache_key] = (int) $presettable->id;

        return (int) $presettable->id;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Entity>  $candidates
     */
    private function entityNamed(\Illuminate\Support\Collection $candidates, ?string $name): ?Entity
    {
        if ($name === null || $name === '') {
            return null;
        }

        return $candidates->firstWhere('name', $name);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Entity>  $candidates
     */
    private function defaultEntity(\Illuminate\Support\Collection $candidates): ?Entity
    {
        return $candidates->first(static fn (Entity $entity): bool => (bool) $entity->getAttribute('is_default'));
    }
}
