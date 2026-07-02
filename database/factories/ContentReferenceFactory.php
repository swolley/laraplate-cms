<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Modules\CMS\Models\Content;
use Modules\CMS\Models\ContentReference;
use Modules\Core\Overrides\Factory;
use Override;

/**
 * @extends Factory<ContentReference>
 */
final class ContentReferenceFactory extends Factory
{
    #[Override]
    protected $model = ContentReference::class;

    #[Override]
    protected function definitionsArray(): array
    {
        return [
            'content_id' => Content::factory(),
            'label' => fake()->company(),
            'url' => fake()->optional()->url(),
            'order_column' => 0,
        ];
    }

    public function withoutUrl(): static
    {
        return $this->state(fn (): array => [
            'url' => null,
        ]);
    }
}
