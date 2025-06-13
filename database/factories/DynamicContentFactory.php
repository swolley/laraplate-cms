<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Helpers\HasDynamicContents;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use Override;
use RuntimeException;

abstract class DynamicContentFactory extends Factory
{
    /**
     * @var EntityType the name of the default entity type
     */
    protected EntityType $entityType;

    #[Override]
    public function definition(): array
    {
        /** @var class-string<Model&HasDynamicContents> $model_name */
        $model_name = $this->modelName();

        /** @var Entity|null $entity */
        $entity = $model_name::fetchAvailableEntities($this->entityType)->random()->first();

        return [
            'entity_id' => $entity?->id,
        ];
    }

    /**
     * @param  Model&HasDynamicContents  $model
     * @param  array<string,mixed>  $forcedValues
     */
    protected function fillContents(Model $model, array $forcedValues = []): void
    {
        if (! $model->entity_id) {
            throw new RuntimeException('No entity specified for model: ' . $model::class);
        }

        /** @var class-string<Model&HasDynamicContents> $model_name */
        $model_name = $this->modelName();

        $all_presets = $model_name::fetchAvailablePresets($this->entityType);

        /** @var Preset|null $preset */
        $preset = $model->preset_id
            ? $all_presets->find($model->preset_id)
            : $all_presets->random()->first();

        if (! $preset) {
            /** @var HasDynamicContents $model */
            throw new RuntimeException("No preset found for entity: {$model->entity->name}");
        }

        /** @var HasDynamicContents $model */
        $model->preset_id = $preset->id;

        // set the components depending on the preset configured fields
        $model->components = $preset->fields->mapWithKeys(function (Field $field) {
            $value = $field->pivot->default;

            if ($field->pivot->is_required || fake()->boolean()) {
                if (isset($forcedValues[$field->name])) {
                    $value = $forcedValues[$field->name];
                } else {
                    $value = match ($field->type) {
                        FieldType::TEXTAREA => fake()->paragraphs(fake()->numberBetween(1, 3), true),
                        FieldType::TEXT => fake()->text(fake()->numberBetween(100, 255)),
                        FieldType::NUMBER => fake()->randomNumber(),
                        FieldType::EMAIL => fake()->unique()->email(),
                        FieldType::PHONE => fake()->boolean() ? fake()->unique()->phoneNumber() : null,
                        FieldType::URL => fake()->boolean() ? fake()->unique()->url() : null,
                        FieldType::JSON => [],
                        default => $value,
                    };
                }
            }

            return [$field->name => $value];
        })->toArray();
    }
}
