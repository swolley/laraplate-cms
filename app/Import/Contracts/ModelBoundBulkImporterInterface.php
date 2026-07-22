<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ModelBoundBulkImporterInterface extends ConnectionAwareBulkImporterInterface
{
    public function importRootModel(): Model;
}
