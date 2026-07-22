<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Import\Support\BulkImportRunner;
use Modules\CMS\Import\Support\ContributorMatcher;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\LocationMatcher;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'database.connections.affinity' => [
            ...config('database.connections.sqlite'),
            'database' => ':memory:',
        ],
    ]);
    DB::purge('affinity');

    Schema::connection('affinity')->create('core_record_origins', static function (Blueprint $table): void {
        $table->id();
        $table->string('referable_type');
        $table->unsignedBigInteger('referable_id');
        $table->string('source_key');
        $table->string('source_label')->nullable();
        $table->string('external_id')->nullable();
        $table->string('url')->nullable();
        $table->timestamps();
    });
    Schema::connection('affinity')->create('cms_contents_translations', static function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('content_id');
        $table->string('locale');
        $table->string('slug');
    });
    Schema::connection('affinity')->create('cms_contributors', static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
    Schema::connection('affinity')->create('cms_contributors_translations', static function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('contributor_id');
        $table->string('locale');
        $table->string('slug');
    });
    Schema::connection('affinity')->create('cms_locations', static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
    });
});

afterEach(function (): void {
    foreach ([
        'core_record_origins',
        'cms_contents_translations',
        'cms_contributors',
        'cms_contributors_translations',
        'cms_locations',
    ] as $table) {
        Schema::connection('affinity')->dropIfExists($table);
    }

    DB::purge('affinity');
});

it('keeps direct BulkImportRunner construction compatible', function (): void {
    expect((new BulkImportRunner)->run(true, static fn (): int => 7))->toBe(7);
});

it('uses the target model connection for origin and dynamic translation lookups', function (): void {
    DB::connection('affinity')->table('cms_contents_translations')->insert([
        'content_id' => 77,
        'locale' => 'it',
        'slug' => 'import-fixture-42',
    ]);

    $content = (new Content)->setConnection('affinity');
    $content->setAttribute('id', 77);
    $content->exists = true;

    $locator = new ExternalReferenceLocator('it');
    $locator->register($content, 'fixture', 41);

    expect(DB::connection('affinity')->table('core_record_origins')->count())->toBe(1)
        ->and(Schema::hasTable('core_record_origins'))->toBeFalse()
        ->and($locator->findImportedRecordId(Content::class, 41, 'fixture', $content))->toBe(77)
        ->and($locator->findImportedRecordId(Content::class, 42, 'fixture', $content))->toBe(77);
});

it('uses owning model connections for contributor and location matches', function (): void {
    DB::connection('affinity')->table('cms_contributors')->insert(['id' => 12, 'name' => 'Redazione']);
    DB::connection('affinity')->table('cms_contributors_translations')->insert([
        'contributor_id' => 12,
        'locale' => 'it',
        'slug' => 'redazione',
    ]);
    DB::connection('affinity')->table('cms_locations')->insert([
        'id' => 15,
        'name' => 'Palazzo Ducale',
        'slug' => 'palazzo-ducale',
    ]);

    $contributor = (new Contributor)->setConnection('affinity');
    $location = (new Location)->setConnection('affinity');

    expect((new ContributorMatcher('it', $contributor))->findExisting('redazione', 'Redazione'))->toBe(12)
        ->and((new LocationMatcher($location))->findExisting('palazzo-ducale', 'Palazzo Ducale'))->toBe(15);
});
