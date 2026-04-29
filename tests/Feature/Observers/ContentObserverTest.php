<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Observers\ContentObserver;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities();
});

it('delegates to set default entity and preset when ids are missing on creating', function (): void {
    $observer = app(ContentObserver::class);
    $content = new Content;
    $content->entity_id = null;
    $content->presettable_id = null;

    $observer->creating($content);

    expect($content)->toBeInstanceOf(Content::class);
});
