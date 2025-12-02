<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Helpers\HasDynamicContentFactory;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\HasTranslationsFactory;
use Override;

final class ContentFactory extends Factory
{
    use HasDynamicContentFactory, HasTranslationsFactory;

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
        $definition = $this->dynamicContentDefinition();
        $valid_from = fake()->boolean() ? now()->addDays(fake()->numberBetween(-10, 10)) : null;
        $valid_to = $valid_from && fake()->boolean() ? $valid_from->addDays(fake()->numberBetween(-10, 10)) : null;

        return $definition + [
            // title, slug, components are now in translations table
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterMaking(function (Content &$content): void {
            $this->fillDynamicContents($content);

            if (array_key_exists('period_to', $content->components) && fake()->boolean()) {
                $content->period_to = max(fake()->dateTime($content->valid_to ?? 'now'), $content->valid_from)->format('Y-m-d H:i:s');
            }

            if (array_key_exists('period_from', $content->components)) {
                $content->period_from = max(fake()->dateTime($content->components['period_to'] ?? $content->valid_to ?? 'now'), $content->valid_from)->format('Y-m-d H:i:s');
            }

            $content->setForcedApprovalUpdate(fake()->boolean(85));
        })->afterCreating(function (Content $content): void {
            // Create default translation
            $this->createTranslations($content, function (string $locale) {
                return [
                    'title' => fake($locale)->text(fake()->numberBetween(100, 255)),
                    'slug' => Str::slug(fake($locale)->text(fake()->numberBetween(100, 255))),
                    'content' => [
                        'blocks' => array_map(fn (string $paragraph) => [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => $paragraph,
                            ],
                        ], fake($locale)->paragraphs(fake()->rand(1, 10))),
                    ],
                ];
            });

            if ($content->exists && $content->getKey()) {
                $this->createRelations($content);
            } else {
                Log::warning('Content model not ready for relations', [
                    'content_id' => $content->getKey(),
                    'exists' => $content->exists,
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
     * Create pivot relations for a content model.
     *
     * @param  Content|Collection<Content>  $content
     */
    public function createRelations(Model|Collection $content, ?callable $callback = null): void
    {
        $this->createDynamicContentRelations($content, function (Content $content) use ($callback): void {
            if (! $content->getKey() || ! $content->exists) {
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
                    $remapped = $relateds->map(fn (Content $related) => [
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
