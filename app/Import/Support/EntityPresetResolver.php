<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable as CmsPresettable;
use Modules\CMS\Models\Preset;
use RuntimeException;

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

    public function entityId(string $entityName, ?ImportConnectionContext $context = null): int
    {
        $entityName = ImportEntityNames::normalize($entityName);

        if (isset($this->entity_ids[$entityName])) {
            return $this->entity_ids[$entityName];
        }

        $context ??= new ImportConnectionContext(new Entity);
        $entity_id = $context->model(Entity::class)->newQuery()
            ->where('name', $entityName)
            ->value('id');

        if ($entity_id === null) {
            throw new RuntimeException("CMS entity not found: {$entityName}");
        }

        $this->entity_ids[$entityName] = (int) $entity_id;

        return (int) $entity_id;
    }

    public function presettableId(string $entityName, string $presetName, ?ImportConnectionContext $context = null): int
    {
        $entityName = ImportEntityNames::normalize($entityName);

        $cache_key = "{$entityName}:{$presetName}";

        if (isset($this->presettable_ids[$cache_key])) {
            return $this->presettable_ids[$cache_key];
        }

        $context ??= new ImportConnectionContext(new Entity);
        $entity_id = $this->entityId($entityName, $context);

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
}
