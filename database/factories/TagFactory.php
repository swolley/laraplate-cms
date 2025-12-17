<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Helpers\HasUniqueFactoryValues;
use Override;

final class TagFactory extends Factory
{
    use HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Cms\Models\Tag::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        return [
            // name, slug are now in translations table
            'type' => fake()->randomElement(['person', 'location', 'organization', null]),
        ];
    }

    /**
     * Configure the factory.
     */
    #[Override]
    public function configure(): self
    {
        return $this
            ->afterMaking(function (\Modules\Cms\Models\Tag $tag): void {
                // Skip validation during creation; translations will be added after creation
                $tag->setSkipValidation(true);
            })
            ->afterCreating(function (\Modules\Cms\Models\Tag $tag): void {
                $default_locale = config('app.locale');

                try {
                    $name = $this->uniqueValue(fn () => fake()->words(fake()->numberBetween(1, 3), true), $this->model, 'name', 50);
                } catch (Exception) {
                    $name = fake()->words(fake()->numberBetween(1, 3), true) . '_' . uniqid();
                }

                $tag->setTranslation($default_locale, [
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);
            });
    }
}
