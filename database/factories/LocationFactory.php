<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Override;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

final class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Location::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $name = fake()->unique()->text(fake()->numberBetween(50, 255));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'country' => fake()->country(),
            'postcode' => fake()->postcode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
