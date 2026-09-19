<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Modules\CMS\Models\Content;
use Modules\CMS\Services\ContentExtenderRegistry;
use Modules\CMS\Tests\Stubs\ContentExtension\StubExtendedThing;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    // getSearchMapping() only supports the concrete engines; the default test engine is Scout's
    // CollectionEngine, so pin the database engine for the mapping assertion.
    Config::set('scout.driver', 'database');
});

it('composes the extension mapping from registered extenders', function (): void {
    app(ContentExtenderRegistry::class)->register('cms.stub_extended', StubExtendedThing::class);

    $mapping = json_encode((new Content())->getSearchMapping());

    expect($mapping)->toContain('extended_type')
        ->and($mapping)->toContain('extension')
        ->and($mapping)->toContain('brand')
        ->and($mapping)->toContain('sku');
});

it('maps extended_type but no extension object when no extender is registered', function (): void {
    $mapping = json_encode((new Content())->getSearchMapping());

    expect($mapping)->toContain('extended_type')
        ->and($mapping)->not->toContain('"extension"');
});
