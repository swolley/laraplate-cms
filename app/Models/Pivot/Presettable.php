<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Modules\CMS\Models\Entity;
use Modules\CMS\Models\Preset;
use Modules\Core\Models\Pivot\Presettable as CorePresettable;
use Override;

/**
 * @property int $version
 * @property array<int, array{field_id: int, name: string, type: string, options: mixed, is_translatable: bool, is_slug: bool, pivot: array{is_required: bool, order_column: int, default: mixed}}> $fields_snapshot
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Eloquent
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
