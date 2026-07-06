<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Dto;

final readonly class ImportGraphDto
{
    /**
     * @param  list<ImportCategoryDto>  $categories
     * @param  list<ImportContributorDto>  $contributors
     * @param  list<ImportTagDto>  $tags
     * @param  list<ImportLocationDto>  $locations
     * @param  list<ImportGraphDto>  $relatedGraphs
     */
    public function __construct(
        public ImportContentDto $content,
        public array $categories = [],
        public array $contributors = [],
        public array $tags = [],
        public array $locations = [],
        public array $relatedGraphs = [],
    ) {}
}
