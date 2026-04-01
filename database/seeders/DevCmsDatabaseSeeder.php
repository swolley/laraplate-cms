<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\ModelObserver;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Contributor;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\BatchSeeder;

final class DevCmsDatabaseSeeder extends BatchSeeder
{
    private const int TARGET_COUNT_CONTRIBUTORS = 15000;

    private const int TARGET_COUNT_CATEGORIES = 500;

    private const int TARGET_COUNT_LOCATIONS = 10000;

    private const int TARGET_COUNT_TAGS = 10000;

    private const int TARGET_COUNT_CONTENTS = 500000;

    protected function execute(): void
    {
        Artisan::call('module:seed', ['module' => 'Cms', '--force' => $this->command->option('force')], outputBuffer: $this->command->getOutput());

        ModelObserver::disableSyncingFor(Content::class);
        ModelObserver::disableSyncingFor(Location::class);

        try {
            Model::unguarded(function (): void {
                $this->seedContributors();
                $this->seedCategories();
                $this->seedLocations();
                $this->seedTags();
                $this->seedContents();
            });
        } finally {
            ModelObserver::enableSyncingFor(Content::class);
            ModelObserver::enableSyncingFor(Location::class);
        }

        Artisan::call('cache:clear');
    }

    private function seedContributors(): void
    {
        $this->createInParallelBatches(Contributor::class, self::TARGET_COUNT_CONTRIBUTORS);
    }

    private function seedCategories(): void
    {
        $this->createInParallelBatches(Category::class, self::TARGET_COUNT_CATEGORIES);
    }

    private function seedLocations(): void
    {
        $this->createInParallelBatches(Location::class, self::TARGET_COUNT_LOCATIONS);
    }

    private function seedTags(): void
    {
        $this->createInParallelBatches(Tag::class, self::TARGET_COUNT_TAGS);
    }

    private function seedContents(): void
    {
        $this->createInParallelBatches(Content::class, self::TARGET_COUNT_CONTENTS);

        // Create pivot relations after contents are created
        $this->createPivotRelations();
    }

    private function createPivotRelations(): void
    {
        $this->command->info('Creating pivot relations...');

        // Get all contents and create relations in batches
        Content::query()->chunk(1000, static function ($contents): void {
            foreach ($contents as $content) {
                Content::factory()->createRelations($content);
            }
        });

        $this->command->info('Pivot relations created successfully!');
    }
}
