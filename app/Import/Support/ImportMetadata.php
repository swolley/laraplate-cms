<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

final class ImportMetadata
{
    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function externalReference(
        int $externalId,
        string $sourceType,
        array $metadata = [],
        ?string $sourceKind = null,
    ): array {
        $base = [
            'external_id' => $externalId,
            'import_source' => $sourceType,
        ];

        if ($sourceKind !== null && $sourceKind !== '') {
            $base['source_kind'] = $sourceKind;
        }

        return array_merge($base, $metadata);
    }
}
