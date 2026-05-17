<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Modules\CMS\Models\Comment;
use Modules\CMS\Models\ContentRating;

final class ContentRatingService
{
    public function syncFromApprovedComment(Comment $comment, ?int $score): void
    {
        if ($score === null || $score < 1 || $score > 5) {
            return;
        }

        ContentRating::query()->updateOrCreate(
            [
                'content_id' => $comment->content_id,
                'user_id' => $comment->user_id,
            ],
            [
                'comment_id' => $comment->id,
                'score' => $score,
            ],
        );
    }
}
