<?php

namespace Modules\Cms\Database\Factories;

use Illuminate\Support\Str;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Category::class;

    /**
     * Define the model's default state.
     */
    #[\Override]
    public function definition(): array
    {
        $parent = fake()->boolean() ? Category::inRandomOrder()->first() : null;
        $entity = Entity::inRandomOrder()->first();

        // Se abbiamo un parent, usiamo il suo entity_id, altrimenti usiamo un entity casuale
        $entity_id = $parent ? $parent->entity_id : ($entity ? $entity->id : null);

        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'entity_id' => $entity_id,
            'parent_id' => $parent?->id ?? null,
            'description' => fake()->boolean() ? fake()->text() : null,
            'persistence' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ];
    }
}
