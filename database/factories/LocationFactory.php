<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Core\Database\Factories\Concerns\HasUniqueFactoryValues;
use Override;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\CMS\Models\Location>
 */
final class LocationFactory extends Factory
{
    use HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = \Modules\CMS\Models\Location::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $name = $this->uniqueValue(static fn () => fake()->text(fake()->numberBetween(50, 255)), $this->model, 'name');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->lexify('??'),
            'country' => fake()->country(),
            'postcode' => fake()->postcode(),
            'geolocation' => new Point(fake()->latitude(), fake()->longitude()),
        ];
    }
}
