<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\ContentRating;
use Modules\Core\Models\User;

/**
 * @extends Factory<ContentRating>
 */
final class ContentRatingFactory extends Factory
{
    protected $model = ContentRating::class;

    public function definition(): array
    {
        return [
            'content_id' => Content::factory(),
            'user_id' => User::factory(),
            'comment_id' => null,
            'score' => fake()->numberBetween(1, 5),
        ];
    }

    public function forComment(int $comment_id): static
    {
        return $this->state(['comment_id' => $comment_id]);
    }
}
