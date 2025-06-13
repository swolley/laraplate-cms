<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use Override;

final class CategoryFactory extends DynamicContentFactory
{
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
        $definition = parent::definition();
        $name = fake()->unique()->words(fake()->numberBetween(1, 3), true) . fake()->numberBetween(1, 1000);

        return $definition + [
            'name' => $name,
            'slug' => Str::slug($name),
            'persistence' => fake()->boolean() ? fake()->numberBetween(1, 1000) : null,
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterMaking(function (Category $category): void {
            $this->fillContents($category);

            $category->setForcedApprovalUpdate(fake()->boolean(90));

            $parent = fake()->boolean(70) ? Category::inRandomOrder()->first() : null;
            $preset = Preset::where('entity_id', $category->entity_id)->inRandomOrder()->first();

            $category->preset_id = $preset->id;
            $category->parent_id = $parent?->id;
        });
    }
}
