<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Unit\Filament\Utils;

use Modules\CMS\Models\Entity;

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
