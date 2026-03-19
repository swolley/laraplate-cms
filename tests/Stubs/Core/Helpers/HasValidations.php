<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

trait HasValidations
{
    public const DEFAULT_RULE = 'default';

    public function getRules(): array
    {
        return [
            self::DEFAULT_RULE => [],
            'create' => [],
            'update' => [],
        ];
    }

    public function getOperationRules(string $operation): array
    {
        return [];
    }
}
