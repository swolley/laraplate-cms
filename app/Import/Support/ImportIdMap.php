<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use LogicException;

final class ImportIdMap
{
    /**
     * @var array<string, int>
     */
    private array $map = [];

    /**
     * @var array<string, string>
     */
    private array $upserted = [];

    public function remember(
        string $entity,
        int $externalId,
        int $localId,
        ?string $connectionName = null,
        ?string $sourceType = null,
    ): void {
        $this->map[$this->key($entity, $externalId, $connectionName, $sourceType)] = $localId;
    }

    public function resolve(
        string $entity,
        int $externalId,
        ?string $connectionName = null,
        ?string $sourceType = null,
    ): ?int {
        $value = $this->map[$this->key($entity, $externalId, $connectionName, $sourceType)] ?? null;

        if ($value !== null || $connectionName !== null || $sourceType !== null) {
            return $value;
        }

        $suffix = "\0{$entity}\0{$externalId}";
        $matches = array_values(array_unique(array_filter(
            $this->map,
            static fn (int $local_id, string $key): bool => str_ends_with($key, $suffix),
            ARRAY_FILTER_USE_BOTH,
        )));

        if (count($matches) > 1) {
            throw new LogicException("Import id [{$entity}:{$externalId}] is ambiguous without connection and source context.");
        }

        return $matches[0] ?? null;
    }

    /**
     * @param  list<int>  $externalIds
     * @return list<int>
     */
    public function resolveMany(
        string $entity,
        array $externalIds,
        ?string $connectionName = null,
        ?string $sourceType = null,
    ): array {
        $resolved = [];

        foreach ($externalIds as $external_id) {
            $local_id = $this->resolve($entity, $external_id, $connectionName, $sourceType);

            if ($local_id !== null) {
                $resolved[] = $local_id;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Records the payload fingerprint an entity was last written with in this run.
     */
    public function markUpserted(
        string $entity,
        int $externalId,
        ?string $connectionName,
        ?string $sourceType,
        string $fingerprint,
    ): void {
        $this->upserted[$this->key($entity, $externalId, $connectionName, $sourceType)] = $fingerprint;
    }

    /**
     * Whether the entity was already written in this run with exactly this payload,
     * so writing it again would change nothing. It also needs a remembered local
     * id, because that is what the caller resolves instead of upserting.
     */
    public function isUpserted(
        string $entity,
        int $externalId,
        ?string $connectionName,
        ?string $sourceType,
        string $fingerprint,
    ): bool {
        $key = $this->key($entity, $externalId, $connectionName, $sourceType);

        return ($this->upserted[$key] ?? null) === $fingerprint && isset($this->map[$key]);
    }

    public function reset(): void
    {
        $this->map = [];
        $this->upserted = [];
    }

    private function key(string $entity, int $externalId, ?string $connectionName, ?string $sourceType): string
    {
        return implode("\0", [
            $connectionName ?? '',
            $sourceType ?? '',
            $entity,
            (string) $externalId,
        ]);
    }
}
