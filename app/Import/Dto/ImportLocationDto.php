<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

/**
 * Minimal location payload imported as data only (no geocoding / core_places resolution).
 */
final readonly class ImportLocationDto
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $externalId = null,
        public string $sourceType = 'cms_import',
    ) {}
}
