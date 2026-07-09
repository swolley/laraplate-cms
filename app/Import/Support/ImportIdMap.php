<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

final class ImportIdMap
{
    /**
     * @var array<string, array<int, int>>
     */
    private array $map = [];

    public function remember(string $entity, int $externalId, int $localId): void
    {
        $this->map[$entity][$externalId] = $localId;
    }

    public function resolve(string $entity, int $externalId): ?int
    {
        return $this->map[$entity][$externalId] ?? null;
    }

    /**
     * @param  list<int>  $externalIds
     * @return list<int>
     */
    public function resolveMany(string $entity, array $externalIds): array
    {
        $resolved = [];

        foreach ($externalIds as $external_id) {
            $local_id = $this->resolve($entity, $external_id);

            if ($local_id !== null) {
                $resolved[] = $local_id;
            }
        }

        return array_values(array_unique($resolved));
    }

    public function reset(): void
    {
        $this->map = [];
    }
}
