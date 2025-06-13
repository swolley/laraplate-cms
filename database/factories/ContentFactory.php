<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Override;

final class ContentFactory extends DynamicContentFactory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Content::class;

    protected EntityType $entityType = EntityType::CONTENTS;

    /**
     * Define the model's default state.
     */
    #[Override]
    public function definition(): array
    {
        $definition = parent::definition();
        $valid_from = fake()->boolean() ? now()->addDays(fake()->numberBetween(-10, 10)) : null;
        $valid_to = $valid_from && fake()->boolean() ? $valid_from->addDays(fake()->numberBetween(-10, 10)) : null;

        return $definition + [
            'title' => fake()->text(fake()->numberBetween(100, 255)),
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterMaking(function (Content $content): void {
            $this->fillContents($content);

            $content->setForcedApprovalUpdate(fake()->boolean(85));

            // convert content into the real class
            $attributes = $content->getAttributes();
            $attributes['components'] = json_decode($attributes['components'], true);
            $content = $content->newInstance($attributes);

            $content->without(['authors']);
        })->afterCreating(function (Content $content): void {
            $authors = Author::inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();
            $content->authors()->attach($authors->map(fn (Author $author) => ['content_id' => $content->id, 'author_id' => $author->id])->toArray());

            $categories = Category::inRandomOrder()->limit(fake()->numberBetween(1, 2))->get();
            $content->categories()->attach($categories->map(fn (Category $category) => ['content_id' => $content->id, 'category_id' => $category->id])->toArray());

            $tags = Tag::inRandomOrder()->limit(fake()->numberBetween(1, 5))->get();
            $content->tags()->attach($tags->map(fn (Tag $tag) => ['content_id' => $content->id, 'tag_id' => $tag->id])->toArray());

            $content->load('authors');
        });
    }
}
