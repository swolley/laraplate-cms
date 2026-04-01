<?php

declare(strict_types=1);

namespace Modules\Core\Search\Traits;

use Modules\Core\Search\Schema\SchemaDefinition;

trait Searchable
{
    public static string $indexedAtField = '_indexed_at';

    public static function withoutSyncingToSearch(callable $callback): mixed
    {
        return $callback();
    }

    public function toSearchableArray(): array
    {
        return [];
    }

    public function getSearchMapping(?SchemaDefinition $schema = null): array
    {
        return [];
    }

    public function getSchemaDefinition(): SchemaDefinition
    {
        return new SchemaDefinition();
    }

    public function searchableAs(): string
    {
        return '';
    }
}
