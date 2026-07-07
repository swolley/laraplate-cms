<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Console\ImportCommand;
use Modules\CMS\Tests\Feature\Import\Stubs\FakeBulkImporter;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create(FakeBulkImporter::TABLE, static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    FakeBulkImporter::$lastArguments = [];
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
        '--bootstrap' => '/tmp/does-not-exist-'.uniqid().'.php',
    ])->assertExitCode(1);
});
