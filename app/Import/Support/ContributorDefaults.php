<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

final class ContributorDefaults
{
    public function __construct(
        private readonly DefaultContributorProvisioner $default_contributor_provisioner,
    ) {}

    public function resolveContributorId(?ImportConnectionContext $context = null): int
    {
        return $this->default_contributor_provisioner->ensure($context);
    }

    public function reset(): void
    {
        $this->default_contributor_provisioner->reset();
    }
}
