<?php

declare(strict_types=1);

namespace Modules\CMS\Contracts;

use ArrayAccess;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\CMS\Models\Tag;

/**
 * Implemented by models using {@see \Modules\CMS\Helpers\HasTags} so lifecycle callbacks are typed.
 */
interface Taggable
{
    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static;

    public function detachTags(array|ArrayAccess $tags, ?string $type = null): static;

    public function tags(): MorphToMany;
}
