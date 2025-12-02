<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Pivot\Presettable;
use RuntimeException;
use stdClass;

/**
 * @property EntityType $entityType the name of the default entity type
 */
trait HasDynamicContentFactory
{
    // private EntityType $entityType;

    public function dynamicContentDefinition(): array
    {
        /** @var class-string<Model&HasDynamicContents> $model_name */
        $model_name = $this->modelName();

        throw_unless(isset($this->entityType), RuntimeException::class, 'Entity type not set for model: ' . $model_name . ' factory class');

        /** @var Entity|null $entity */
        $presettable = $model_name::fetchAvailablePresettables($this->entityType)->random();

        return [
            'entity_id' => $presettable?->entity_id,
            'presettable_id' => $presettable?->id,
        ];
    }

    /**
     * Create pivot relations for a content model.
     *
     * @param  Model&HasDynamicContents|Collection<Model&HasDynamicContents>  $content
     */
    public function createDynamicContentRelations(Model|Collection $content, ?callable $callback = null): void
    {
        try {
            if (! $callback) {
                return;
            }

            if (! $content instanceof Collection) {
                $content = collect([$content]);
            }

            $i = 0;

            for ($i; $i < $content->count(); $i++) {
                $callback($content[$i]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to attach relations to content: ' . $e->getMessage(), [
                'model_id' => $content instanceof Model ? $content->getKey() : $content->get($i)->getKey(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  Model&HasDynamicContents  $model
     * @param  array<string,mixed>  $forcedValues
     */
    private function fillDynamicContents(Model $model, array $forcedValues = []): void
    {
        throw_unless($model->entity_id, RuntimeException::class, 'No entity specified for model: ' . $model::class);

        $model->loadRelation('presettable');

        /** @var class-string<Model&HasDynamicContents> $model_name */
        $model_name = $this->modelName();

        throw_unless(isset($this->entityType), RuntimeException::class, 'Entity type not set for model: ' . $model_name);

        // /** @var Collection<Presettable> $all_presettables */
        // $all_presettables = $model_name::fetchAvailablePresettables($this->entityType);

        // $presettable = $model->presettable_id
        //     ? $all_presettables->where('entity_id', $model->entity_id)->where('id', $model->presettable_id)->first()
        //     : $all_presettables->where('entity_id', $model->entity_id)->random();

        // if (! $presettable) {
        //     /** @var HasDynamicContents $model */
        //     throw new RuntimeException("No presettable found for entity: {$model->entity->name}");
        // }

        // /** @var HasDynamicContents $model */
        // $model->presettable_id = $presettable->id;
        // $model->entity_id = $presettable->entity_id;

        // set the components depending on the preset configured fields
        $model->components = $presettable->preset->fields->mapWithKeys(function (Field $field) use ($forcedValues): array {
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
                        FieldType::EDITOR => (object) [
                            'blocks' => array_map(fn (string $paragraph) => (object) [
                                'type' => 'paragraph',
                                'data' => [
                                    'text' => $paragraph,
                                ],
                            ], fake()->paragraphs(fake()->numberBetween(1, 10))),
                        ],
                        FieldType::OBJECT => new stdClass(),
                        default => $value,
                    };
                }
            }

            return [$field->name => $value];
        })->toArray();

        if (class_uses_trait($model, HasSlug::class) && $model->getRawOriginal('slug') === null) {
            $model->slug = $model->generateSlug();
        }
    }
}
