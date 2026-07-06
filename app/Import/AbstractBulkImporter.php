<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Import\Contracts\RecordMapperInterface;
use Modules\CMS\Import\Contracts\SourceIteratorInterface;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportPostProcessor;

abstract class AbstractBulkImporter implements BulkImporterInterface
{
    /**
     * @param  SourceIteratorInterface<array<string, mixed>>  $iterator
     * @param  RecordMapperInterface<array<string, mixed>>  $mapper
     */
    public function __construct(
        protected readonly SourceIteratorInterface $iterator,
        protected readonly RecordMapperInterface $mapper,
        protected readonly ImportPipeline $pipeline,
        protected readonly ImportPostProcessor $post_processor,
    ) {}

    public function import(): int
    {
        $imported = 0;

        foreach ($this->iterator->records() as $source) {
            $graph = $this->mapper->mapGraph($source);

            if ($graph === null) {
                continue;
            }

            $this->pipeline->import($graph);
            $imported++;
        }

        $this->post_processor->run(
            clearCaches: (bool) config('cms.import.post_import.clear_caches', true),
            reindex: (bool) config('cms.import.post_import.reindex', false),
        );

        return $imported;
    }
}
