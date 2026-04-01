<?php

declare(strict_types=1);

namespace Modules\Cms\Contracts;

use ArrayAccess;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Cms\Models\Tag;

/**
 * Implemented by models using {@see \Modules\Cms\Helpers\HasTags} so lifecycle callbacks are typed.
 */
interface Taggable
{
    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static;

    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static;

    public function tags(): MorphToMany;
}
