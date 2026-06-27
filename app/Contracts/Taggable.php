<?php

declare(strict_types=1);

namespace Modules\CMS\Contracts;

use ArrayAccess;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
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

    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static;

    public function attachTag(string|Tag $tag, ?string $type = null): static;

    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static;

    public function tags(): MorphToMany;
}
