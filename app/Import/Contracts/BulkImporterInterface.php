<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Contracts;

interface BulkImporterInterface
{
    /**
     * Run a bulk import from the configured source.
     *
     * @return int Number of content records imported.
     */
    public function import(): int;
}
