<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Preset;
use Modules\Core\Models\Pivot\Presettable as CorePresettable;
use Override;

/**
 * @mixin IdeHelperPresettable
 */
final class Presettable extends CorePresettable
{
    #[Override]
    protected function presetModelClass(): string
    {
        return Preset::class;
    }

    #[Override]
    protected function entityModelClass(): string
    {
        return Entity::class;
    }
}
