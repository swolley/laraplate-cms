<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

trait HasApprovals
{
    protected bool $forced_approval_update = false;

    protected static string $valid_from_column = 'valid_from';

    protected static string $valid_to_column = 'valid_to';

    public function toArray(array $parsed = []): array
    {
        return $parsed;
    }

    public function setForcedApprovalUpdate(bool $forced): static
    {
        $this->forced_approval_update = $forced;

        return $this;
    }

    protected function requiresApprovalWhen(array $modifications): bool
    {
        return false;
    }
}
