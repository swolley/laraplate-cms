<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Contracts;

use Iterator;

/**
 * @template TSource of array<string, mixed>
 */
interface SourceIteratorInterface
{
    /**
     * @return Iterator<int, TSource>
     */
    public function records(): Iterator;
}
