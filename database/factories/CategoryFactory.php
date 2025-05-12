<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Entity;
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

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->boolean() ? fake()->text() : null,
            'persistence' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterMaking(function (Category $category): void {
            $parent = fake()->boolean() ? Category::inRandomOrder()->first() : null;

            if ($parent) {
                $entity_id = $parent->entity_id;
            } else {
                $entity_id = fake()->boolean() ? Entity::inRandomOrder()->first()?->entity_id : null;
            }
            $category->entity_id = $entity_id;
            $category->parent_id = $parent?->id;
            $category->parent_entity_id = $parent?->entity_id;
        });
    }
}
