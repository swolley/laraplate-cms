<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Support\BulkImportRunner;
use Modules\CMS\Import\Support\ContributorMatcher;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\LocationMatcher;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Models\Translations\ContributorTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\RecordOrigin;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'database.connections.affinity' => [
            ...config('database.connections.sqlite'),
            'database' => ':memory:',
        ],
    ]);
    DB::purge('affinity');

    $content = (new Content)->setConnection('affinity');
    $connection_name = $content->getConnection()->getName();
    $record_origin = (new RecordOrigin)->setConnection($connection_name);
    $content_translation = (new ContentTranslation)->setConnection($connection_name);
    $contributor = (new Contributor)->setConnection($connection_name);
    $contributor_translation = (new ContributorTranslation)->setConnection($connection_name);
    $location = (new Location)->setConnection($connection_name);
    $schema = $content->getConnection()->getSchemaBuilder();

    $schema->create($record_origin->getTable(), static function (Blueprint $table): void {
        $table->id();
        $table->string('referable_type');
        $table->unsignedBigInteger('referable_id');
        $table->string('source_key');
        $table->string('source_label')->nullable();
        $table->string('external_id')->nullable();
        $table->string('url')->nullable();
        $table->timestamps();
    });
    $schema->create($content_translation->getTable(), static function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('content_id');
        $table->string('locale');
        $table->string('slug');
    });
    $schema->create($contributor->getTable(), static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
    $schema->create($contributor_translation->getTable(), static function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('contributor_id');
        $table->string('locale');
        $table->string('slug');
    });
    $schema->create($location->getTable(), static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
    });
});

afterEach(function (): void {
    $content = (new Content)->setConnection('affinity');
    $schema = $content->getConnection()->getSchemaBuilder();

    foreach ([
        (new RecordOrigin)->setConnection($content->getConnection()->getName())->getTable(),
        (new ContentTranslation)->setConnection($content->getConnection()->getName())->getTable(),
        (new Contributor)->setConnection($content->getConnection()->getName())->getTable(),
        (new ContributorTranslation)->setConnection($content->getConnection()->getName())->getTable(),
        (new Location)->setConnection($content->getConnection()->getName())->getTable(),
    ] as $table) {
        $schema->dropIfExists($table);
    }

    DB::purge('affinity');
});

it('keeps direct BulkImportRunner construction compatible', function (): void {
    expect((new BulkImportRunner)->run(true, static fn (): int => 7))->toBe(7);
});

it('uses the target model connection for origin and dynamic translation lookups', function (): void {
    $content = (new Content)->setConnection('affinity');
    $connection_name = $content->getConnection()->getName();
    $content_translation = (new ContentTranslation)->setConnection($connection_name);
    $record_origin = (new RecordOrigin)->setConnection($connection_name);

    $content_translation->getConnection()->table($content_translation->getTable())->insert([
        'content_id' => 77,
        'locale' => 'it',
        'slug' => 'import-fixture-42',
    ]);

    $content->setAttribute('id', 77);
    $content->exists = true;

    $locator = new ExternalReferenceLocator('it');
    $locator->register($content, 'fixture', 41);

    $default_connection = DB::connection(config('database.default'));

    expect($record_origin->getConnection()->table($record_origin->getTable())->count())->toBe(1)
        ->and($default_connection->getSchemaBuilder()->hasTable($record_origin->getTable()))->toBeFalse()
        ->and($locator->findImportedRecordId(Content::class, 41, 'fixture', $content))->toBe(77)
        ->and($locator->findImportedRecordId(Content::class, 42, 'fixture', $content))->toBe(77);
});

it('uses owning model connections for contributor and location matches', function (): void {
    $contributor = (new Contributor)->setConnection('affinity');
    $contributor_translation = (new ContributorTranslation)->setConnection($contributor->getConnection()->getName());
    $location = (new Location)->setConnection('affinity');

    $contributor->getConnection()->table($contributor->getTable())->insert(['id' => 12, 'name' => 'Redazione']);
    $contributor_translation->getConnection()->table($contributor_translation->getTable())->insert([
        'contributor_id' => 12,
        'locale' => 'it',
        'slug' => 'redazione',
    ]);
    $location->getConnection()->table($location->getTable())->insert([
        'id' => 15,
        'name' => 'Palazzo Ducale',
        'slug' => 'palazzo-ducale',
    ]);

    expect((new ContributorMatcher('it', $contributor))->findExisting('redazione', 'Redazione'))->toBe(12)
        ->and((new LocationMatcher($location))->findExisting('palazzo-ducale', 'Palazzo Ducale'))->toBe(15);
});
