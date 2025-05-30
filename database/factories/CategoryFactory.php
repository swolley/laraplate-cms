<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use Override;

final class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);
        $entity = Entity::where('type', EntityType::CATEGORIES)->inRandomOrder()->first();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'persistence' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
            'entity_id' => $entity->id,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterMaking(function (Category $category): void {
            $category->setForcedApprovalUpdate(fake()->boolean(90));

            $parent = fake()->boolean(70) ? Category::inRandomOrder()->first() : null;
            $preset = Preset::where('entity_id', $category->entity_id)->inRandomOrder()->first();

            $category->preset_id = $preset->id;
            $category->parent_id = $parent?->id;

            // set the components depending on the preset configured fields
            $category->components = $preset->fields->mapWithKeys(function (Field $field) {
                $value = $field->pivot->default;

                if ($field->pivot->is_required || fake()->boolean()) {
                    $value = match ($field->type) {
                        FieldType::TEXTAREA => fake()->paragraphs(fake()->numberBetween(1, 3), true),
                        FieldType::TEXT => fake()->text(fake()->numberBetween(100, 255)),
                        FieldType::NUMBER => fake()->randomNumber(),
                        FieldType::URL => fake()->boolean() ? fake()->unique()->url() : null,
                        default => $field->pivot->default,
                    };
                }

                return [$field->name => $value];
            })->toArray();
        });
    }
}
