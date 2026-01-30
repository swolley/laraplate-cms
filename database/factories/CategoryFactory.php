<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContentFactory;
use Modules\Cms\Models\Category;
use Modules\Core\Helpers\HasUniqueFactoryValues;
use Override;

final class CategoryFactory extends Factory
{
    use HasDynamicContentFactory, HasUniqueFactoryValues;

    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Category::class;

    protected EntityType $entityType = EntityType::CATEGORIES;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $definition = $this->dynamicContentDefinition();

        return $definition + [];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterMaking(function (Category $category): void {
            // Ensure translatable fields are set before first save
            $name = fake()->unique()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);
            $category->name = $name;
            $category->slug = Str::slug($name);

            $this->fillDynamicContents($category);

            $category->setForcedApprovalUpdate(fake()->boolean(90));

            $parent = fake()->boolean(70) ? Category::inRandomOrder()->first() : null;
            $category->parent_id = $parent?->id;
        })->afterCreating(function (Category $category): void {
            // Create default translation
            $default_locale = config('app.locale');
            $category->setTranslation($default_locale, [
                'name' => $category->name,
                'slug' => $category->slug,
                'components' => $category->components ?? [],
            ]);
        });
    }
}
