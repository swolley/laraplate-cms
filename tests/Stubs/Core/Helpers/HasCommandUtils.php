<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

trait HasCommandUtils
{
    public function validationCallback(string $attribute, mixed $value, array $validations): ?string
    {
        return null;
    }
}
