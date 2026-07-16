<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use FilesystemIterator;
use Modules\CMS\Import\Contracts\BulkImporterInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

/**
 * Locates a sibling laraplate-importers project and discovers concrete
 * {@see BulkImporterInterface} implementations under its src/ tree.
 */
final class SiblingImportersDiscovery
{
    public function __construct(
        private readonly ?string $root_override = null,
    ) {}

    public function root(): ?string
    {
        $root = $this->root_override ?? base_path('../laraplate-importers');

        return is_dir($root) ? $root : null;
    }

    public function autoloadPath(?string $root = null): ?string
    {
        $root ??= $this->root();

        if ($root === null) {
            return null;
        }

        $autoload = $root . '/vendor/autoload.php';

        return is_file($autoload) ? $autoload : null;
    }

    /**
     * @return list<class-string>
     */
    public function discoverImplementations(?string $root = null): array
    {
        $root ??= $this->root();

        if ($root === null) {
            return [];
        }

        $src_root = $root . '/src';

        if (! is_dir($src_root)) {
            return [];
        }

        $found = [];

        /** @var SplFileInfo $file */
        foreach ($this->phpFiles($src_root) as $file) {
            $fqcn = $this->classNameFromPhpFile($file->getPathname());

            if ($fqcn === null || ! class_exists($fqcn)) {
                continue;
            }

            $reflection = new ReflectionClass($fqcn);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                continue;
            }

            if (! $reflection->implementsInterface(BulkImporterInterface::class)) {
                continue;
            }

            $found[] = $fqcn;
        }

        sort($found);

        return array_values(array_unique($found));
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFiles(string $src_root): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src_root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            yield $file;
        }
    }

    private function classNameFromPhpFile(string $path): ?string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $namespace = null;

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $matches) === 1) {
            $namespace = mb_trim($matches[1]);
        }

        if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/m', $contents, $matches) !== 1) {
            return null;
        }

        $class = $matches[1];

        if ($namespace === null || $namespace === '') {
            return $class;
        }

        return $namespace . '\\' . $class;
    }
}
