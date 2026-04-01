<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\HasUniqueFactoryValues;
use Modules\Core\Overrides\Factory;
use Override;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Cms\Models\Tag>
 */
final class TagFactory extends Factory
{
    use HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    protected function definitionsArray(): array
    {
        return [
            // name, slug are now in translations table
            'type' => fake()->randomElement(['person', 'location', 'organization', null]),
        ];
    }

    #[Override]
    protected function beforeFactoryMaking(Model $model): void
    {
        if (! $model instanceof Tag) {
            return;
        }

        // Skip validation during creation; translations will be added after creation.
        $model->setSkipValidation(true);
    }

    #[Override]
    protected function translatedFieldsArray(Model $model): array
    {
        if (! $model instanceof Tag) {
            return [];
        }

        try {
            $name = $this->uniqueValue(static fn () => fake()->words(fake()->numberBetween(1, 3), true), $this->model, 'name', 50);
        } catch (Exception) {
            $name = fake()->words(fake()->numberBetween(1, 3), true) . '_' . uniqid();
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
