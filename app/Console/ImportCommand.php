<?php

declare(strict_types=1);

namespace Modules\CMS\Console;

use Modules\CMS\Import\Support\CmsBulkImporterResolver;
use Modules\CMS\Import\Support\SiblingImportersDiscovery;
use Modules\Core\Console\AbstractImportCommand;
use Modules\Core\Import\Support\BulkImportRunner;
use Override;

final class ImportCommand extends AbstractImportCommand
{
    #[Override]
    protected $name = 'cms:import';

    #[Override]
    protected $description = 'Run a bulk content import through an external importer plugin <fg=cyan>(📰 Modules\\CMS)</fg=cyan>';

    public function __construct(
        BulkImportRunner $runner,
        CmsBulkImporterResolver $resolver,
        SiblingImportersDiscovery $discovery,
    ) {
        parent::__construct($runner, $resolver, $discovery);
    }
}
