<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\User;

/**
 * @extends Factory<Comment>
 */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content_id' => Content::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function withBody(string $body, ?string $locale = null): static
    {
        return $this->afterCreating(function (Comment $comment) use ($body, $locale): void {
            $locale ??= LocaleContext::get();

            $comment->translations()->updateOrCreate(
                ['locale' => $locale],
                ['body' => $body],
            );
        });
    }

    public function approved(string $body = 'Approved comment body.'): static
    {
        return $this->withBody($body);
    }
}
