<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
        return $this->afterMaking(function (Content &$content): void {
            $this->fillContents($content);

            if (array_key_exists('period_to', $content->components) && fake()->boolean()) {
                $content->period_to = max(fake()->dateTime($content->valid_to ?? 'now'), $content->valid_from)->format('Y-m-d H:i:s');
            }

            if (array_key_exists('period_from', $content->components)) {
                $content->period_from = max(fake()->dateTime($content->components['period_to'] ?? $content->valid_to ?? 'now'), $content->valid_from)->format('Y-m-d H:i:s');
            }

            $content->setForcedApprovalUpdate(fake()->boolean(85));
        })->afterCreating(function (Content $content): void {
            if ($content->exists && $content->getKey()) {
                $this->createRelations($content);
            } else {
                Log::warning('Content model not ready for relations', [
                    'content_id' => $content->getKey(),
                    'exists' => $content->exists
                ]);
            }
        });
    }

    #[Override]
    public function newModel(array $attributes = []): Content
    {
        return (new Content())->newInstance($attributes);
    }

    /**
     * Create pivot relations for a content model
     *
     * @param  Content|Collection<Content>  $content
     */
    #[Override]
    public function createRelations(Model|Collection $content, ?callable $callback = null): void
    {
        parent::createRelations($content, function (Content $content) use ($callback) {
            if (!$content->getKey() || !$content->exists) {
                return;
            }

            if ($content->doesntHave('authors')) {
                $authors = Author::inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();
                if ($authors->isNotEmpty()) {
                    $content->authors()->syncWithoutDetaching($authors->pluck('id')->toArray());
                } else {
                    $content->authors()->sync($authors->pluck('id')->toArray());
                }
            }

            if ($content->doesntHave('categories')) {
                $categories = Category::inRandomOrder()->limit(fake()->numberBetween(1, 2))->get();
                if ($categories->isNotEmpty()) {
                    $content->categories()->syncWithoutDetaching($categories->pluck('id')->toArray());
                }
            }

            if (fake()->boolean(70) && $content->doesntHave('tags')) {
                $tags = Tag::inRandomOrder()->limit(fake()->numberBetween(1, 5))->get();
                if ($tags->isNotEmpty()) {
                    $content->tags()->syncWithoutDetaching($tags->pluck('id')->toArray());
                }
            }

            if (fake()->boolean(35) && $content->doesntHave('related')) {
                $relateds = Content::inRandomOrder()->where('id', '!=', $content->id)->limit(fake()->numberBetween(1, 3))->get();
                if ($relateds->isNotEmpty()) {
                    $remapped = $relateds->map(fn(Content $related) => [
                        'related_content_id' => $related->id,
                        'content_id' => $content->id,
                    ])->toArray();
                    $content->related()->syncWithoutDetaching($remapped);
                }
            }

            if ($callback) {
                $callback($content);
            }
        });
    }
}
