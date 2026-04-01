<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Factories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Contributor;
use Modules\Cms\Models\Tag;
use Modules\Core\Overrides\Factory;
use Override;

/**
 * @extends \Modules\Core\Overrides\Factory<\Modules\Cms\Models\Content>
 */
final class ContentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    #[Override]
    protected $model = Content::class;

    protected EntityType $entityType = EntityType::CONTENTS;

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

            if ($content->doesntHave('contributors')) {
                $contributors = Contributor::query()->inRandomOrder()->limit(fake()->numberBetween(1, 3))->get();

                if ($contributors->isNotEmpty()) {
                    $content->contributors()->syncWithoutDetaching($contributors->pluck('id')->toArray());
                } else {
                    $content->contributors()->sync($contributors->pluck('id')->toArray());
                }
            }

            if ($content->doesntHave('categories')) {
                $categories = Category::query()->inRandomOrder()->limit(fake()->numberBetween(1, 2))->get();

                if ($categories->isNotEmpty()) {
                    $content->categories()->syncWithoutDetaching($categories->pluck('id')->toArray());
                }
            }

            if (fake()->boolean(70) && $content->doesntHave('tags')) {
                $tags = Tag::query()->inRandomOrder()->limit(fake()->numberBetween(1, 5))->get();

                if ($tags->isNotEmpty()) {
                    $content->tags()->syncWithoutDetaching($tags->pluck('id')->toArray());
                }
            }

            if (fake()->boolean(35) && $content->doesntHave('related')) {
                $relateds = Content::query()->inRandomOrder()->where('id', '!=', $content->id)->limit(fake()->numberBetween(1, 3))->get();

                if ($relateds->isNotEmpty()) {
                    $remapped = $relateds->map(fn (Content $related): array => [
                        'related_content_id' => $related->id,
                        'content_id' => $content->id,
                    ])->all();
                    $content->related()->syncWithoutDetaching($remapped);
                }
            }

            if ($callback) {
                $callback($content);
            }
        });
    }

    /**
     * Define the model's default state.
     */
    #[Override]
    protected function definitionsArray(): array
    {
        $valid_from = fake()->boolean() ? now()->addDays(fake()->numberBetween(-10, 10)) : null;
        $valid_to = $valid_from && fake()->boolean() ? $valid_from->addDays(fake()->numberBetween(-10, 10)) : null;

        return [
            // title, slug, components are now in translations table
            'valid_from' => $valid_from,
            'valid_to' => $valid_to,
        ];
    }

    #[Override]
    protected function beforeFactoryMaking(Model $model): void
    {
        if (! $model instanceof Content) {
            return;
        }

        // Pre-set required translatable fields before validation and before components-driven logic.
        $title = fake()->sentence(fake()->numberBetween(3, 8));
        $model->title = $title;
        $model->slug = Str::slug($title);

        // Note: dynamic contents are filled by the base factory after this hook.
    }

    #[Override]
    protected function afterFactoryMaking(Model $model): void
    {
        if (! $model instanceof Content) {
            return;
        }

        // components are available after base fillDynamicContents(); now we can adjust derived fields.
        if (array_key_exists('period_to', $model->components) && fake()->boolean()) {
            $model->period_to = max(fake()->dateTime($model->valid_to ?? 'now'), $model->valid_from)->format('Y-m-d H:i:s');
        }

        if (array_key_exists('period_from', $model->components)) {
            $model->period_from = max(fake()->dateTime($model->components['period_to'] ?? $model->valid_to ?? 'now'), $model->valid_from)->format('Y-m-d H:i:s');
        }
    }

    #[Override]
    protected function translatedFieldsArray(Model $model): array
    {
        if (! $model instanceof Content) {
            return [];
        }

        return [
            'title' => $model->title,
            'slug' => $model->slug,
            'components' => $model->components ?? [],
        ];
    }
}
