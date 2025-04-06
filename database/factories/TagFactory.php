<?php

namespace Modules\Cms\Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Tag::class;

    /**
     * Define the model's default state.
     */
    #[\Override]
    public function definition(): array
    {
        try {
            $name = fake()->unique()->words(fake()->numberBetween(1, 3), true);
        } catch (\Exception $e) {
            $name = fake()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(['person', 'location', 'organization', null]),
        ];
    }
}
