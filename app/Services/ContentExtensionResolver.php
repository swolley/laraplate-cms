<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\CMS\Models\Content;
use RuntimeException;

/**
 * Batched upcast for the content-extension seam.
 *
 * Given a page of {@see Content} (loaded with `withExtended()`), it returns the same collection with
 * each extended content replaced by its extender, the content re-attached via `setRelation('content')`.
 * It never mutates the base `Content` query and never lazy-loads per row: the cost is one query per
 * distinct alias on the page (C5, C6). An `extended_type` whose alias is not registered fails loud
 * (C12); a registered alias with no owner row is a data-integrity error (C15).
 */
final readonly class ContentExtensionResolver
{
    public function __construct(private ContentExtenderRegistry $registry) {}

    /**
     * @param  Collection<int, Content>  $contents
     * @return Collection<int, Content|Model>
     */
    public function resolve(Collection $contents): Collection
    {
        /** @var array<string, list<int|string>> $idsByAlias */
        $idsByAlias = [];

        foreach ($contents as $content) {
            $alias = $content->extended_type;

            if ($alias !== null) {
                $idsByAlias[$alias][] = $content->getKey();
            }
        }

        /** @var array<int|string, Model> $extendersByContentId */
        $extendersByContentId = [];

        foreach ($idsByAlias as $alias => $contentIds) {
            /** @var class-string<Model> $class */
            $class = $this->registry->resolve($alias);

            // Skip the extender's own `content` eager-load: we re-attach the page's content below,
            // so loading it again would add a query per alias. The extender's other eager-loads stay.
            foreach ($class::query()->without('content')->whereIn('content_id', $contentIds)->get() as $extender) {
                $contentId = $extender->getAttribute('content_id');

                if (is_int($contentId) || is_string($contentId)) {
                    $extendersByContentId[$contentId] = $extender;
                }
            }
        }

        return $contents->map(function (Content $content) use ($extendersByContentId): Content|Model {
            $alias = $content->extended_type;

            if ($alias === null) {
                return $content;
            }

            $contentKey = $content->getKey();

            if (! is_int($contentKey) && ! is_string($contentKey)) {
                return $content;
            }

            $extender = $extendersByContentId[$contentKey] ?? null;

            if (! $extender instanceof Model) {
                throw new RuntimeException(
                    "Extended content [{$contentKey}] (alias [{$alias}]) has no extender row.",
                );
            }

            $extender->setRelation('content', $content);

            return $extender;
        });
    }
}
