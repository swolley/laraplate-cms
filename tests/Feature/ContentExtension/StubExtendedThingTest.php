<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\Stubs\ContentExtension\StubExtendedThing;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities([EntityType::Contents]);

    Schema::create('cms_stub_extended_things', function ($table): void {
        $table->id();
        $table->foreignId('content_id')->unique();
        $table->string('brand')->nullable();
        $table->string('sku')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
});

it('persists and links a staged content and stamps extended_type (C2)', function (): void {
    $content = Content::factory()->make();

    $stub = new StubExtendedThing(['brand' => 'Acme', 'sku' => 'SKU-1']);
    $stub->setTempContent($content);
    $stub->save();

    expect($stub->content_id)->not->toBeNull()
        ->and($content->exists)->toBeTrue();

    $stored = DB::table(CMSTables::Contents->value)->where('id', $stub->content_id)->value('extended_type');
    expect($stored)->toBe('cms.stub_extended');
});

it('resolves the back-relation despite the hide scope (C8)', function (): void {
    $content = Content::factory()->make();
    $stub = new StubExtendedThing(['brand' => 'Acme']);
    $stub->setTempContent($content);
    $stub->save();

    // Fresh load through the relation (with $with = ['content']), not the setRelation shortcut.
    $reloaded = StubExtendedThing::query()->findOrFail($stub->getKey());

    expect($reloaded->content)->not->toBeNull()
        ->and($reloaded->content->getKey())->toBe($stub->content_id);
});

it('never changes extended_type on a normal content save (C17)', function (): void {
    $content = Content::factory()->make();
    $stub = new StubExtendedThing(['brand' => 'Acme']);
    $stub->setTempContent($content);
    $stub->save();

    $content->order_column = 7;
    $content->save();

    $stored = DB::table(CMSTables::Contents->value)->where('id', $content->getKey())->value('extended_type');
    expect($stored)->toBe('cms.stub_extended');
});

it('forbids a second extender on the same content (C15)', function (): void {
    $content = Content::factory()->create();

    $first = new StubExtendedThing(['content_id' => $content->getKey(), 'brand' => 'A']);
    $first->save();

    $second = new StubExtendedThing(['content_id' => $content->getKey(), 'brand' => 'B']);
    $second->save();
})->throws(QueryException::class);
