<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\Core\Import\Contracts\ImportPluginDiscoveryInterface;
use Modules\Core\Import\Support\FilesystemImportPluginDiscovery;

/**
 * Locates a sibling laraplate-importers project and discovers concrete
 * {@see BulkImporterInterface} implementations under its src/ tree.
 */
final readonly class SiblingImportersDiscovery implements ImportPluginDiscoveryInterface
{
    private FilesystemImportPluginDiscovery $discovery;

    public function __construct(
        ?string $root_override = null,
    ) {
        $this->discovery = new FilesystemImportPluginDiscovery(
            label: 'laraplate-importers',
            defaultRoot: $root_override ?? base_path('../laraplate-importers'),
            contract: BulkImporterInterface::class,
        );
    }

    public function label(): string
    {
        return $this->discovery->label();
    }

    public function root(): ?string
    {
        return $this->discovery->root();
    }

    public function autoloadPath(?string $root = null): ?string
    {
        return $this->discovery->autoloadPath($root);
    }

    /**
     * @return list<class-string>
     */
    public function discoverImplementations(?string $root = null): array
    {
        return $this->discovery->discoverImplementations($root);
    }
}
