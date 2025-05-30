<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Casts\FieldType;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Field;
use Modules\Cms\Models\Preset;
use Modules\Cms\Models\Tag;
use Override;

final class ContentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Content::class;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $entity = Entity::where('type', EntityType::CONTENTS)->inRandomOrder()->first();

        $valid_from = fake()->boolean() ? now()->addDays(fake()->numberBetween(-10, 10)) : null;
        $valid_to = $valid_from && fake()->boolean() ? $valid_from->addDays(fake()->numberBetween(-10, 10)) : null;

        return [
            'title' => fake()->text(fake()->numberBetween(100, 255)),
            'entity_id' => $entity->id,
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterMaking(function (Content $content): void {
            $content->setForcedApprovalUpdate(fake()->boolean(70));

            // convert content into the real class
            $attributes = $content->getAttributes();
            $attributes['components'] = json_decode($attributes['components'], true);
            $content = $content->newInstance($attributes);

            $content->without(['authors']);
            $preset = Preset::where('entity_id', $content->entity_id)->inRandomOrder()->first();

            // set the components depending on the preset configured fields
            $content->components = $preset->fields->mapWithKeys(function (Field $field) {
                $value = $field->pivot->default;

                if ($field->pivot->is_required || fake()->boolean()) {
                    $value = match ($field->type) {
                        FieldType::TEXTAREA => fake()->paragraphs(fake()->numberBetween(1, 3), true),
                        FieldType::TEXT => fake()->text(fake()->numberBetween(100, 255)),
                        FieldType::NUMBER => fake()->randomNumber(),
                        FieldType::URL => fake()->boolean() ? fake()->unique()->url() : null,
                        default => $field->pivot->default,
                    };
                }

                return [$field->name => $value];
            })->toArray();
        })->afterCreating(function (Content $content): void {
            $authors = Author::inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();
            $content->authors()->attach($authors->map(fn (Author $author) => ['content_id' => $content->id, 'author_id' => $author->id])->toArray());

            $categories = Category::inRandomOrder()->forEntity($content->entity_id)->limit(fake()->numberBetween(1, 2))->get();
            $content->categories()->attach($categories->map(fn (Category $category) => ['content_id' => $content->id, 'category_id' => $category->id])->toArray());

            $tags = Tag::inRandomOrder()->limit(fake()->numberBetween(1, 5))->get();
            $content->tags()->attach($tags->map(fn (Tag $tag) => ['content_id' => $content->id, 'tag_id' => $tag->id])->toArray());

            $content->load('authors');
        });
    }
}
