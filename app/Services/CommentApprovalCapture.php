<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Support\Collection;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\ContentRating;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\Modification;

final class CommentApprovalCapture
{
    /**
     * @param  array<string, array{original: mixed, modified: mixed}>  $diff
     * @return array<string, array{original: mixed, modified: mixed}>
     */
    public static function enrichDiff(Comment $comment, array $diff): array
    {
        if ($comment->hasPendingBodyForCurrentLocale()) {
            $locale = LocaleContext::get();
            $existing = $comment->getOriginalTranslation();

            $diff['body'] = [
                'original' => $existing?->body,
                'modified' => $comment->pending_translations[$locale]['body'] ?? $comment->body,
            ];
        }

        if (isset($diff['body'])) {
            $diff['locale'] = [
                'original' => null,
                'modified' => LocaleContext::get(),
            ];
        }

        if ($comment->content_id !== null) {
            $diff['content_id'] = [
                'original' => $comment->getOriginal('content_id'),
                'modified' => $comment->content_id,
            ];
        }

        if ($comment->parent_id !== null) {
            $diff['parent_id'] = [
                'original' => $comment->getOriginal('parent_id'),
                'modified' => $comment->parent_id,
            ];
        }

        if ($comment->pending_rating_score !== null) {
            $existing_score = ContentRating::query()
                ->where('content_id', $comment->content_id)
                ->where('user_id', $comment->user_id)
                ->value('score');

            $diff['rating_score'] = [
                'original' => $existing_score !== null ? (int) $existing_score : null,
                'modified' => $comment->pending_rating_score,
            ];
        }

        return $diff;
    }

    public static function capture(Comment $comment): bool
    {
        $diff = Collection::make($comment->getDirty())
            ->transform(static fn (mixed $value, string $key): array => [
                'original' => $comment->getOriginal($key),
                'modified' => $comment->{$key},
            ])
            ->all();

        $diff = self::enrichDiff($comment, $diff);

        if ($diff === []) {
            return true;
        }

        $has_modification_pending = $comment->modifications()
            ->activeOnly()
            ->where('md5', md5(json_encode($diff)))
            ->first();

        $modifier = auth()->user();

        /** @var class-string<Modification> $modification_class */
        $modification_class = config('approval.models.modification', Modification::class);

        /** @var Modification $modification */
        $modification = $has_modification_pending ?? new $modification_class();
        $modification->active = true;
        $modification->modifications = $diff;
        $modification->approvers_required = 1;
        $modification->disapprovers_required = 1;
        $modification->md5 = md5(json_encode($diff));

        if ($modifier !== null) {
            $modifier_class = $modifier::class;
            $modifier_instance = new $modifier_class();
            $modification->modifier_id = $modifier->{$modifier_instance->getKeyName()};
            $modification->modifier_type = $modifier_class;
        }

        if ($comment->getKey() === null) {
            $modification->is_update = false;
        }

        $modification->save();

        if ($has_modification_pending === null) {
            $comment->modifications()->save($modification);
        }

        return false;
    }
}
