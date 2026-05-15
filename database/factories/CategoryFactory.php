<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\Core\Overrides\Factory;
use Override;

/**
 * @extends \Modules\Core\Overrides\Factory<\Modules\CMS\Models\Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = Category::class;

    protected EntityType $entityType = EntityType::Categories;

    #[Override]
    protected function definitionsArray(): array
    {
        return [];
    }

    #[Override]
    protected function beforeFactoryMaking(Model $model): void
    {
        if (! $model instanceof Category) {
            return;
        }

        // Ensure translatable fields are set before first save.
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);
        $model->name = $name;
        $model->slug = Str::slug($name);
        $parent = fake()->boolean(70) ? Category::query()->inRandomOrder()->first() : null;
        $model->parent_id = $parent?->id;
    }

    #[Override]
    protected function translatedFieldsArray(Model $model): array
    {
        if (! $model instanceof Category) {
            return [];
        }

        return [
            'name' => $model->name,
            'slug' => $model->slug,
            'components' => $model->components ?? [],
        ];
    }
}
