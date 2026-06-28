<?php

declare(strict_types=1);

namespace Modules\CMS\Contracts;

use ArrayAccess;
use Modules\CMS\Models\Tag;

/**
 * Implemented by models using {@see \Modules\CMS\Helpers\HasTags} so lifecycle callbacks are typed.
 *
 * @property list<string|Tag> $queuedTags
 */
interface Taggable
{
    /**
     * @return list<string|Tag>
     */
    public function getQueuedTags(): array;

    public function clearQueuedTags(): void;

    /**
     * @param  array<int|string, string|Tag>|ArrayAccess<int|string, string|Tag>|string|Tag  $tags
     */
    public function attachTags(array|ArrayAccess|string|Tag $tags, ?string $type = null): static;

    public function attachTag(string|Tag $tag, ?string $type = null): static;

    /**
     * @param  array<int|string, string|Tag>|ArrayAccess<int|string, string|Tag>  $tags
     */
    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static;
}
