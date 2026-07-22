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
use RuntimeException;

final class ImportPresetProvisioner
{
    public function __construct(
        private readonly PresetVersioningService $preset_versioning_service,
    ) {}

    public function provisionFromGraph(ImportGraphDto $graph, ?ImportConnectionContext $context = null): void
    {
        $context ??= new ImportConnectionContext(new Entity);
        $this->assertContext($context);
        $this->ensurePreset($graph->content->entityName, $graph->content->presetName, $context);

        foreach ($graph->categories as $category) {
            $this->ensurePreset($category->entityName, $category->presetName, $context);
        }

        foreach ($graph->contributors as $contributor) {
            $this->ensurePreset($contributor->entityName, $contributor->presetName, $context);
        }
    }

    public function assertContext(ImportConnectionContext $context): void
    {
        $context->preflight($this->participantModelClasses());
    }

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public function participantModelClasses(): array
    {
        return [Entity::class, Preset::class, CmsPresettable::class, Field::class];
    }

    public function ensurePreset(string $entityName, string $presetName, ?ImportConnectionContext $context = null): CmsPresettable
    {
        $context ??= new ImportConnectionContext(new Entity);
        $entity = $context->model(Entity::class)->newQuery()->firstOrCreate(
            ['name' => $entityName],
            [
                'slug' => $entityName,
                'type' => $this->entityTypeForName($entityName),
            ],
        );

        $preset = $context->model(Preset::class)->newQuery()->firstOrCreate(
            ['entity_id' => $entity->id, 'name' => $presetName],
            ['entity_id' => $entity->id, 'name' => $presetName],
        );

        $definitions = $this->fieldDefinitions($entityName, $presetName);

        if ($definitions !== []) {
            $this->syncFields($preset, $definitions, $context);
        }

        $active = $context->model(CmsPresettable::class)->newQuery()
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
        return config('cms.import.presets', []);
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
     * @param  array<string, array{type: FieldType|string, translatable?: bool, required?: bool, options?: array<string, mixed>}>  $definitions
     */
    private function syncFields(Preset $preset, array $definitions, ImportConnectionContext $context): void
    {
        $changed = false;

        foreach ($definitions as $field_name => $definition) {
            $field = $this->ensureField($field_name, $definition, $changed, $context);

            if (! $preset->fields()->where('field_id', $field->id)->exists()) {
                $preset->fields()->attach($field->id, [
                    'default' => null,
                    'is_required' => (bool) ($definition['required'] ?? false),
                ]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->preset_versioning_service->createVersion($preset);
        }
    }

    /**
     * Field-level attributes (type, options, is_translatable) are the source of
     * truth captured by the presettable snapshot, so they must live on the Field
     * itself and never on the fieldable pivot.
     *
     * @param  array{type: FieldType|string, translatable?: bool, required?: bool, options?: array<string, mixed>}  $definition
     */
    private function ensureField(string $fieldName, array $definition, bool &$changed, ImportConnectionContext $context): Field
    {
        $type = $definition['type'] instanceof FieldType
            ? $definition['type']
            : FieldType::from((string) $definition['type']);
        $is_translatable = (bool) ($definition['translatable'] ?? false);
        $options = (object) ($definition['options'] ?? []);

        $field_model = $context->model(Field::class);
        $field = $field_model->newQuery()->where('name', $fieldName)->first();

        if (! $field instanceof Field) {
            $field = $field_model->newInstance();
            $field->name = $fieldName;
            $field->type = $type;
            $field->is_translatable = $is_translatable;
            $field->options = $options;
            $field->save();
            $changed = true;

            return $field;
        }

        // type and is_translatable are frozen once a field exists (they define
        // where content data is stored). A mismatch is a modeling conflict that
        // must be surfaced, not silently changed: use a distinct field name.
        if ($field->type !== $type || $is_translatable !== (bool) $field->is_translatable) {
            throw new RuntimeException("Field [{$fieldName}] already exists with a different type/translatability; use a distinct field name.");
        }

        return $field;
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
