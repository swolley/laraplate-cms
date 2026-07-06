<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Dto\ImportGraphDto;
use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Pivot\Presettable as CmsPresettable;
use Modules\CMS\Models\Preset;
use Modules\Core\Casts\FieldType;
use Modules\Core\Models\Field;
use Modules\Core\Services\PresetVersioningService;
use stdClass;

final class ImportPresetProvisioner
{
    public function __construct(
        private readonly PresetVersioningService $preset_versioning_service,
    ) {}

    public function provisionFromGraph(ImportGraphDto $graph): void
    {
        if ($this->configuredPresets() === []) {
            return;
        }

        $this->ensurePreset($graph->content->entityName, $graph->content->presetName);

        foreach ($graph->categories as $category) {
            $this->ensurePreset($category->entityName, $category->presetName);
        }

        foreach ($graph->contributors as $contributor) {
            $this->ensurePreset($contributor->entityName, $contributor->presetName);
        }
    }

    public function ensurePreset(string $entityName, string $presetName): CmsPresettable
    {
        $entity = Entity::query()->firstOrCreate(
            ['name' => $entityName],
            [
                'slug' => $entityName,
                'type' => $this->entityTypeForName($entityName),
            ],
        );

        $preset = Preset::query()->firstOrCreate(
            ['entity_id' => $entity->id, 'name' => $presetName],
            ['entity_id' => $entity->id, 'name' => $presetName],
        );

        $definitions = $this->fieldDefinitions($entityName, $presetName);

        if ($definitions !== []) {
            $this->syncFields($preset, $definitions);
        }

        $active = CmsPresettable::query()
            ->where('preset_id', $preset->id)
            ->where('entity_id', $entity->id)
            ->whereNull('deleted_at')
            ->latest('version')
            ->first();

        if ($active instanceof CmsPresettable) {
            return $active;
        }

        return $this->preset_versioning_service->createVersion($preset);
    }

    /**
     * @return array<string, mixed>
     */
    private function configuredPresets(): array
    {
        /** @var array<string, mixed> $presets */
        $presets = config('cms.import.presets', []);

        return $presets;
    }

    /**
     * @return array<string, array{type: FieldType, translatable?: bool, required?: bool}>
     */
    private function fieldDefinitions(string $entityName, string $presetName): array
    {
        /** @var array<string, array<string, array{type: string, translatable?: bool, required?: bool}>> $presets */
        $presets = config('cms.import.presets', []);

        return $presets[$entityName][$presetName] ?? [];
    }

    /**
     * @param  array<string, array{type: FieldType, translatable?: bool, required?: bool}>  $definitions
     */
    private function syncFields(Preset $preset, array $definitions): void
    {
        $changed = false;

        foreach ($definitions as $field_name => $definition) {
            $field = Field::query()->firstOrCreate(
                ['name' => $field_name],
                [
                    'type' => $definition['type'] instanceof FieldType
                        ? $definition['type']
                        : FieldType::from((string) $definition['type']),
                    'options' => new stdClass(),
                ],
            );

            if (! $preset->fields()->where('field_id', $field->id)->exists()) {
                $preset->fields()->attach($field->id, [
                    'default' => null,
                    'is_required' => (bool) ($definition['required'] ?? false),
                    'is_translatable' => (bool) ($definition['translatable'] ?? false),
                ]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->preset_versioning_service->createVersion($preset);
        }
    }

    private function entityTypeForName(string $entityName): \Modules\CMS\Casts\EntityType
    {
        return match ($entityName) {
            'contributors', 'contributor' => \Modules\CMS\Casts\EntityType::Contributors,
            'categories', 'category', 'section', 'folder' => \Modules\CMS\Casts\EntityType::Categories,
            default => \Modules\CMS\Casts\EntityType::Contents,
        };
    }
}
