<?php

declare(strict_types=1);

namespace Modules\CMS\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\ModelObserver;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\Core\Helpers\BatchSeeder;

final class DevCMSDatabaseSeeder extends BatchSeeder
{
    private const int TARGET_COUNT_CONTRIBUTORS = 15_000;

    private const int TARGET_COUNT_CATEGORIES = 1_000;

    private const int TARGET_COUNT_LOCATIONS = 10_000;

    private const int TARGET_COUNT_TAGS = 300_000;

    private const int TARGET_COUNT_CONTENTS = 500_000;

    protected function execute(): void
    {
        Artisan::call('module:seed', ['module' => 'CMS', '--force' => $this->command->option('force')], outputBuffer: $this->command->getOutput());

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
