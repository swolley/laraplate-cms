<?php

declare(strict_types=1);

namespace Modules\CMS\Observers;

use Modules\CMS\Models\Content;
use Modules\CMS\Models\ContentReference;
use Modules\CMS\Scopes\HidesExtendedContent;
use Modules\Core\Overrides\LocaleScope;

/**
 * Re-sends a content to the search engine when one of its references is added, changed or removed, because
 * the references (labels and URL hosts) are part of the content's search document and a change of theirs
 * never touches the content row itself.
 */
final class ContentReferenceObserver
{
    /**
     * Handle the ContentReference "saved" event (fires for both create and update).
     */
    public function saved(ContentReference $reference): void
    {
        $this->reindex($reference);
    }

    /**
     * Handle the ContentReference "deleted" event.
     */
    public function deleted(ContentReference $reference): void
    {
        $this->reindex($reference);
    }

    /**
     * The owning content is resolved past the locale and the extended-content scopes: the document of a
     * content with no translation in the current locale, or of an extended content, must follow its
     * references as well. A trashed content is not indexed, so it is left alone.
     */
    private function reindex(ContentReference $reference): void
    {
        $content = Content::query()
            ->withoutGlobalScope(LocaleScope::class)
            ->withoutGlobalScope(HidesExtendedContent::class)
            ->find($reference->content_id);

        if ($content instanceof Content) {
            $content->searchable();
        }
    }
}
