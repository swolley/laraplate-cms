<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Models\Content;
use Modules\Core\Services\DynamicContentsService;
use Modules\Core\Services\DynamicEntityService;

final class ImportPostProcessor
{
    public function run(bool $clearCaches = true, bool $reindex = false): void
    {
        if ($clearCaches) {
            DynamicContentsService::getInstance()->clearAllCaches();
            resolve(DynamicEntityService::class)->clearAllCaches();
        }

        if ($reindex && config('scout.driver') !== null) {
            Content::makeAllSearchable();
        }
    }
}
