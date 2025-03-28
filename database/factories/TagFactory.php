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
        // $base_name = fake()->unique()->words(fake()->numberBetween(1, 3), true);
        // $name = $base_name . ' ' . fake()->numberBetween(1, 9999);
        // while (Tag::where('name', $name)->exists()) {
        //     $name = $base_name . ' ' . fake()->numberBetween(1, 9999);
        // }
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(['person', 'location', 'organization', null]),
        ];
    }
}
