<?php

declare(strict_types=1);

namespace Modules\Core\Search\Schema;

final class FieldDefinition
{
    /**
     * @param  list<IndexType>  $indexes
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $name,
        public FieldType $type,
        public array $indexes = [],
        public array $options = [],
    ) {}
}
