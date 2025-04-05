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
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'entity_id' => $parent?->entity_id ?? Entity::inRandomOrder()->first()?->id,
            'parent_id' => $parent?->id ?? null,
            'description' => fake()->boolean() ? fake()->text() : null,
            'persistence' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ];
    }
}
