<?php

declare(strict_types=1);

namespace Modules\CMS\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Contracts\ExtendsContent;
use Modules\CMS\Models\Content;
use Modules\CMS\Services\ContentExtenderRegistry;
use Modules\CMS\Services\ContentExtensionCascade;

/**
 * Reverse leg of the content-extension lifecycle (C9): a content deleted directly is applied to its
 * extender per the extender's declared policy — mandatory content cascades to delete the extender,
 * optional content orphans it (`content_id` nulled). Guarded so it does not collide with the
 * extender→content cascade (C10).
 */
final readonly class ContentExtensionObserver
{
    public function __construct(private ContentExtenderRegistry $registry) {}

    public function deleting(Content $content): void
    {
        if (ContentExtensionCascade::$active) {
            return;
        }

        $alias = $content->extended_type;

        if ($alias === null || ! $this->registry->has($alias)) {
            return;
        }

        /** @var class-string<Model> $class */
        $class = $this->registry->resolve($alias);

        $extender = $class::query()->whereIn('content_id', [$content->getKey()])->first();

        if (! $extender instanceof Model) {
            return;
        }

        ContentExtensionCascade::guard(function () use ($content, $extender): void {
            $mandatory = ! $extender instanceof ExtendsContent || $extender->contentIsMandatory();

            if (! $mandatory) {
                $extender->setAttribute('content_id', null);
                $extender->save();

                return;
            }

            if ($content->isForceDeleting()) {
                $extender->forceDelete();

                return;
            }

            $extender->delete();
        });
    }
}
