<?php

declare(strict_types=1);

namespace Modules\Cms\Tests\Unit\Filament\Utils;

use Modules\Cms\Models\Entity;

final class FakeEntityFilamentResource
{
    /**
     * @return class-string<Entity>
     */
    public static function getModel(): string
    {
        return Entity::class;
    }
}
