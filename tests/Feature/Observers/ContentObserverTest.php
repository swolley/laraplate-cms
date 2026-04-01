<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Content;
use Modules\Cms\Observers\ContentObserver;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCmsEntities();
});

it('delegates to set default entity and preset when ids are missing on creating', function (): void {
    $observer = app(ContentObserver::class);
    $content = new Content;
    $content->entity_id = null;
    $content->presettable_id = null;

    $observer->creating($content);

    expect($content)->toBeInstanceOf(Content::class);
});
