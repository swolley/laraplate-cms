<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Modules\CMS\Ai\Prompts\CommentModerationPrompt;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\Core\Contracts\ModerationAdapter;
use Modules\Core\Data\ModerationInput;
use Modules\Core\Data\ModerationRequest;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\Modification;

final readonly class CommentModerationAdapter implements ModerationAdapter
{
    public const string PROFILE = 'cms.comment';

    public function supports(Modification $modification): bool
    {
        return $modification->modifiable_type === Comment::class;
    }

    public function build(Modification $modification): ModerationRequest
    {
        $changes = $modification->modifications;
        $content_id = (int) ($changes['content_id']['modified'] ?? 0);
        $body = (string) ($changes['body']['modified'] ?? '');
        $locale = (string) ($changes['locale']['modified'] ?? LocaleContext::get());

        /** @var Content $content */
        $content = Content::query()
            ->withoutGlobalScopes()
            ->with(['translations', 'presettable.entity'])
            ->findOrFail($content_id);

        $entity_name = $content->presettable?->entity?->name ?? '';

        $context_sections = [
            'Article title' => $this->resolveContentTitle($content, $locale),
            'Article type' => $entity_name,
            'Article excerpt' => $this->plainTextExcerpt($content, $locale, maxChars: 1500),
        ];

        $parent_body = $this->resolveParentCommentBody($changes, $locale);

        if ($parent_body !== '') {
            $context_sections['Parent comment being replied to'] = $parent_body;
        }

        $input = new ModerationInput(
            subjectText: $body,
            locale: $locale,
            contextSections: $context_sections,
            profile: self::PROFILE,
        );

        return new ModerationRequest(
            input: $input,
            systemPrompt: CommentModerationPrompt::system(),
            userPrompt: CommentModerationPrompt::user($input),
        );
    }

    /**
     * @param  array<string, array{original: mixed, modified: mixed}>  $changes
     */
    private function resolveParentCommentBody(array $changes, string $locale): string
    {
        $parent_id = (int) ($changes['parent_id']['modified'] ?? 0);

        if ($parent_id <= 0) {
            return '';
        }

        /** @var Comment|null $parent */
        $parent = Comment::query()
            ->withoutGlobalScopes()
            ->with('translations')
            ->find($parent_id);

        if ($parent === null) {
            return '';
        }

        $translation = $parent->getTranslation($locale, with_fallback: true);

        return mb_trim((string) ($translation?->body ?? ''));
    }

    private function resolveContentTitle(Content $content, string $locale): string
    {
        $translation = $content->translations->firstWhere('locale', $locale)
            ?? $content->translations->first();

        return (string) ($translation?->title ?? '');
    }

    private function plainTextExcerpt(Content $content, string $locale, int $maxChars): string
    {
        $parts = [];

        $previous_locale = LocaleContext::get();
        LocaleContext::set($locale);

        try {
            foreach (['short_content', 'content', 'subtitle'] as $field) {
                $value = $content->{$field} ?? null;

                if (is_string($value) && $value !== '') {
                    $parts[] = $value;
                }
            }
        } finally {
            LocaleContext::set($previous_locale);
        }

        $text = strip_tags(implode("\n\n", $parts));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = mb_trim($text);

        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars) . '…';
    }
}
