<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\CMS\Models\Tag;
use Modules\Core\Database\Factories\Concerns\HasUniqueFactoryValues;
use Modules\Core\Overrides\Factory;
use Override;

/**
 * @extends \Modules\Core\Overrides\Factory<\Modules\CMS\Models\Tag>
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
            // words(..., true) is declared array|string by Faker even though the
            // second argument is what makes it a string; implode covers both.
            $name = $this->uniqueValue(static function (): string {
                $words = fake()->words(fake()->numberBetween(1, 3), true);

                return is_array($words) ? implode(' ', $words) : $words;
            }, $this->model, 'name', 50);
        } catch (Exception) {
            $name = fake()->words(fake()->numberBetween(1, 3), true) . '_' . uniqid();
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
