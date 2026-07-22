<?php

declare(strict_types=1);

namespace Modules\CMS\Import;

use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Import\Contracts\ModelBoundBulkImporterInterface;
use Modules\CMS\Import\Contracts\RecordMapperInterface;
use Modules\CMS\Import\Contracts\SourceIteratorInterface;
use Modules\CMS\Import\Pipeline\ImportPipeline;
use Modules\CMS\Import\Support\ImportPostProcessor;
use Modules\CMS\Models\Content;

abstract class AbstractBulkImporter implements BulkImporterInterface, ModelBoundBulkImporterInterface
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

    public function importRootModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Content;
    }

    public function importConnection(): \Illuminate\Database\ConnectionInterface
    {
        return $this->importRootModel()->getConnection();
    }

    public function import(): int
    {
        $this->pipeline->resetState();
        $imported = 0;

        try {
            foreach ($this->iterator->records() as $source) {
                $graph = $this->mapper->mapGraph($source);

                if ($graph === null) {
                    continue;
                }

                $this->pipeline->import($graph, $this->importRootModel());
                $imported++;
            }

            $this->post_processor->run(
                clearCaches: (bool) config('cms.import.post_import.clear_caches', true),
                reindex: (bool) config('cms.import.post_import.reindex', false),
            );

            return $imported;
        } finally {
            $this->pipeline->resetState();
        }
    }
}
