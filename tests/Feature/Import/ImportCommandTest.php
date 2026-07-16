<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Console\ImportCommand;
use Modules\CMS\Import\Support\SiblingImportersDiscovery;
use Modules\CMS\Tests\Feature\Import\Stubs\FakeBulkImporter;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create(FakeBulkImporter::TABLE, static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    FakeBulkImporter::$lastArguments = [];

    // Isolate from a real sibling laraplate-importers checkout on the host machine.
    $this->app->instance(
        SiblingImportersDiscovery::class,
        new SiblingImportersDiscovery(sys_get_temp_dir() . '/cms-importers-absent-' . uniqid('', true)),
    );
});

afterEach(function (): void {
    Schema::dropIfExists(FakeBulkImporter::TABLE);
});

it('resolves the importer by FQCN, forwards args and reports the imported count', function (): void {
    $this->artisan(ImportCommand::class, [
        '--importer' => FakeBulkImporter::class,
        '--arg' => ['records=3'],
    ])
        ->expectsOutputToContain('Imported 3 record(s)')
        ->assertExitCode(0);

    expect(DB::table(FakeBulkImporter::TABLE)->count())->toBe(3)
        ->and(FakeBulkImporter::$lastArguments['records'])->toBe('3');
});

it('honours the --limit option', function (): void {
    $this->artisan(ImportCommand::class, [
        '--importer' => FakeBulkImporter::class,
        '--arg' => ['records=5'],
        '--limit' => 2,
    ])->assertExitCode(0);

    expect(DB::table(FakeBulkImporter::TABLE)->count())->toBe(2)
        ->and(FakeBulkImporter::$lastArguments['limit'])->toBe(2);
});

it('rolls back all writes in --dry-run', function (): void {
    $this->artisan(ImportCommand::class, [
        '--importer' => FakeBulkImporter::class,
        '--arg' => ['records=4'],
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Dry-run enabled')
        ->assertExitCode(0);

    expect(DB::table(FakeBulkImporter::TABLE)->count())->toBe(0)
        ->and(FakeBulkImporter::$lastArguments['dryRun'])->toBeTrue();
});

it('fails when the importer class cannot be found', function (): void {
    $this->artisan(ImportCommand::class, [
        '--importer' => 'Does\\Not\\Exist',
    ])->assertExitCode(1);
});

it('fails when --importer is missing', function (): void {
    $this->artisan(ImportCommand::class)->assertExitCode(1);
});

it('requires an external bootstrap autoloader before resolving', function (): void {
    $bootstrap = tempnam(sys_get_temp_dir(), 'cms_import_bootstrap_') . '.php';
    file_put_contents($bootstrap, "<?php\nif (! defined('CMS_IMPORT_BOOTSTRAP_LOADED')) { define('CMS_IMPORT_BOOTSTRAP_LOADED', true); }\n");

    try {
        $this->artisan(ImportCommand::class, [
            '--importer' => FakeBulkImporter::class,
            '--bootstrap' => $bootstrap,
            '--arg' => ['records=1'],
        ])->assertExitCode(0);

        expect(defined('CMS_IMPORT_BOOTSTRAP_LOADED'))->toBeTrue();
    } finally {
        @unlink($bootstrap);
    }
});

it('fails when the bootstrap file does not exist', function (): void {
    $this->artisan(ImportCommand::class, [
        '--importer' => FakeBulkImporter::class,
        '--bootstrap' => '/tmp/does-not-exist-' . uniqid() . '.php',
    ])->assertExitCode(1);
});

it('fails when the sibling importers autoload prompt is declined', function (): void {
    $fixture = createSiblingImportersFixture();

    try {
        $this->app->instance(SiblingImportersDiscovery::class, new SiblingImportersDiscovery($fixture['root']));

        $this->artisan(ImportCommand::class)
            ->expectsConfirmation($fixture['confirm'], 'no')
            ->assertExitCode(1);
    } finally {
        removeDirectory($fixture['root']);
    }
});

it('fails when an importer is offered but the user skips selection', function (): void {
    $fixture = createSiblingImportersFixture();

    try {
        $this->app->instance(SiblingImportersDiscovery::class, new SiblingImportersDiscovery($fixture['root']));

        $this->artisan(ImportCommand::class)
            ->expectsConfirmation($fixture['confirm'], 'yes')
            ->expectsChoice('Select an importer (optional)', '(skip)', $fixture['choices'])
            ->assertExitCode(1);
    } finally {
        removeDirectory($fixture['root']);
    }
});

it('loads sibling autoload and runs the selected importer', function (): void {
    $fixture = createSiblingImportersFixture();

    try {
        $this->app->instance(SiblingImportersDiscovery::class, new SiblingImportersDiscovery($fixture['root']));

        $this->artisan(ImportCommand::class, [
            '--arg' => ['records=2'],
        ])
            ->expectsConfirmation($fixture['confirm'], 'yes')
            ->expectsChoice('Select an importer (optional)', $fixture['importer'], $fixture['choices'])
            ->expectsOutputToContain('Imported 2 record(s)')
            ->assertExitCode(0);

        expect(DB::table(FakeBulkImporter::TABLE)->count())->toBe(2);
    } finally {
        removeDirectory($fixture['root']);
    }
});

/**
 * @return array{root: string, confirm: string, importer: string, choices: list<string>}
 */
function createSiblingImportersFixture(): array
{
    $root = sys_get_temp_dir() . '/cms-sibling-importers-' . uniqid('', true);
    $class_dir = $root . '/src/Demo/Importers';
    $vendor_dir = $root . '/vendor';

    mkdir($class_dir, 0777, true);
    mkdir($vendor_dir, 0777, true);

    $importer = 'Demo\\Importers\\SelectableBulkImporter';

    file_put_contents($class_dir . '/SelectableBulkImporter.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Demo\Importers;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Modules\CMS\Tests\Feature\Import\Stubs\FakeBulkImporter;

final class SelectableBulkImporter implements BulkImporterInterface
{
    public function __construct(
        public readonly string $records = '0',
        public readonly bool $dryRun = false,
        public readonly ?int $limit = null,
    ) {}

    public function import(): int
    {
        $total = max(0, (int) $this->records);

        if ($this->limit !== null && $this->limit > 0) {
            $total = min($total, $this->limit);
        }

        for ($i = 0; $i < $total; $i++) {
            DB::table(FakeBulkImporter::TABLE)->insert(['name' => "sibling-{$i}"]);
        }

        return $total;
    }
}
PHP);

    file_put_contents($vendor_dir . '/autoload.php', <<<'PHP'
<?php

spl_autoload_register(static function (string $class): void {
    if ($class !== 'Demo\\Importers\\SelectableBulkImporter') {
        return;
    }

    require dirname(__DIR__) . '/src/Demo/Importers/SelectableBulkImporter.php';
});
PHP);

    return [
        'root' => $root,
        'confirm' => 'Found laraplate-importers at ' . $root . '. Load its Composer autoloader?',
        'importer' => $importer,
        'choices' => ['(skip)', $importer],
    ];
}

function removeDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $items = scandir($directory);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . '/' . $item;

        if (is_dir($path)) {
            removeDirectory($path);

            continue;
        }

        @unlink($path);
    }

    @rmdir($directory);
}
