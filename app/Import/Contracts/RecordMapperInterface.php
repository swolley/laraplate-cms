<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Contracts;

use Modules\CMS\Import\Dto\ImportGraphDto;

/**
 * @template TSource of array<string, mixed>
 */
interface RecordMapperInterface
{
    /**
     * @param  TSource  $source
     */
    public function mapGraph(array $source): ?ImportGraphDto;
}
