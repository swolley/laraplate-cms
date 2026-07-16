<?php

declare(strict_types=1);

use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Import\Support\SiblingImportersDiscovery;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('returns null when the sibling project directory is missing', function (): void {
    $discovery = new SiblingImportersDiscovery(sys_get_temp_dir() . '/missing-laraplate-importers-' . uniqid('', true));

    expect($discovery->root())->toBeNull()
        ->and($discovery->autoloadPath())->toBeNull()
        ->and($discovery->discoverImplementations())->toBe([]);
});

it('discovers concrete BulkImporterInterface classes under src', function (): void {
    $root = sys_get_temp_dir() . '/cms-discovery-' . uniqid('', true);
    $class_dir = $root . '/src/Demo/Importers';
    mkdir($class_dir, 0777, true);
    mkdir($root . '/vendor', 0777, true);

    file_put_contents($class_dir . '/ConcreteImporter.php', <<<'PHP'
<?php

namespace Demo\Importers;

use Modules\CMS\Import\Contracts\BulkImporterInterface;

final class ConcreteImporter implements BulkImporterInterface
{
    public function import(): int
    {
        return 0;
    }
}
PHP);

    file_put_contents($class_dir . '/AbstractImporter.php', <<<'PHP'
<?php

namespace Demo\Importers;

use Modules\CMS\Import\Contracts\BulkImporterInterface;

abstract class AbstractImporter implements BulkImporterInterface
{
}
PHP);

    file_put_contents($class_dir . '/NotAnImporter.php', <<<'PHP'
<?php

namespace Demo\Importers;

final class NotAnImporter
{
}
PHP);

    file_put_contents($root . '/vendor/autoload.php', <<<'PHP'
<?php

spl_autoload_register(static function (string $class): void {
    $map = [
        'Demo\\Importers\\ConcreteImporter' => dirname(__DIR__) . '/src/Demo/Importers/ConcreteImporter.php',
        'Demo\\Importers\\AbstractImporter' => dirname(__DIR__) . '/src/Demo/Importers/AbstractImporter.php',
        'Demo\\Importers\\NotAnImporter' => dirname(__DIR__) . '/src/Demo/Importers/NotAnImporter.php',
    ];

    if (! isset($map[$class])) {
        return;
    }

    require $map[$class];
});
PHP);

    try {
        require_once $root . '/vendor/autoload.php';

        $discovery = new SiblingImportersDiscovery($root);

        expect($discovery->root())->toBe($root)
            ->and($discovery->autoloadPath())->toBe($root . '/vendor/autoload.php')
            ->and($discovery->discoverImplementations())->toBe([
                'Demo\\Importers\\ConcreteImporter',
            ])
            ->and(is_subclass_of('Demo\\Importers\\ConcreteImporter', BulkImporterInterface::class))->toBeTrue();
    } finally {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($root);
    }
});
