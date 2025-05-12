<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cms\Casts\EntityType;
use Override;

final class EntityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Entity::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'type' => fake()->randomElement(EntityType::cases()),
        ];
    }
}
