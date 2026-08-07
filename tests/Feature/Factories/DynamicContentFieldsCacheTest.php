<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\ModelObserver;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves preset fields from the service cache instead of loading them per record', function (): void {
    setupCMSEntities([EntityType::Contents, EntityType::Contributors, EntityType::Categories]);

    Content::disableVersioning();
    ModelObserver::disableSyncingFor(Content::class);

    try {
        // Warm the DynamicContentsService caches (entities/presets/presettables).
        Content::factory()->create();

        DB::enableQueryLog();
        Content::factory()->create();

        // fillDynamicContents no longer loads preset fields from the database:
        // they come from the cached preset. (Residual presettable/preset reads
        // belong to the model's own save/validation path, not the factory.)
        $field_queries = collect(DB::getQueryLog())
            ->filter(fn (array $q): bool => str_contains(mb_strtolower($q['query']), 'core_fields'))
            ->count();

        expect($field_queries)->toBe(0);
    } finally {
        ModelObserver::enableSyncingFor(Content::class);
        Content::enableVersioning();
    }
});
