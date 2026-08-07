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
use Modules\Core\Concurrency\BatchTask;
use Modules\Core\Helpers\BatchSeeder;

final class DevCMSDatabaseSeeder extends BatchSeeder
{
    private const int PIVOT_CHUNK_SIZE = 1_000;

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
            $this->withoutModelVersioning(
                [Content::class, Contributor::class, Category::class, Location::class, Tag::class],
                fn () => Model::unguarded(function (): void {
                    $this->seedContributors();
                    $this->seedCategories();
                    $this->seedLocations();
                    $this->seedTags();
                    $this->seedContents();
                }),
            );
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

        // Build the candidate id pools once (picking from them in PHP avoids an
        // ORDER BY RAND() query per content), then relate contents in parallel:
        // one fork per chunk of content ids. The pools live in the parent and are
        // inherited by each fork through copy-on-write.
        $pools = Content::factory()->buildRelationIdPools();
        $content_ids = $pools['contents'];

        if ($content_ids === []) {
            $this->command->info('No contents to relate.');

            return;
        }

        $connection_name = Content::query()->getConnection()->getName();
        $tasks = [];

        foreach (array_chunk($content_ids, self::PIVOT_CHUNK_SIZE) as $index => $chunk_ids) {
            $tasks[] = new BatchTask(
                id: "pivots_{$index}",
                units: count($chunk_ids),
                run: static function () use ($chunk_ids, $pools): int {
                    $contents = Content::query()->whereKey($chunk_ids)->get();
                    Content::factory()->createRelations($contents, null, $pools);

                    return count($chunk_ids);
                },
            );
        }

        $this->runParallelTasks($tasks, 'Creating pivot relations (parallel)', $connection_name, self::PIVOT_CHUNK_SIZE);

        $this->command->info('Pivot relations created successfully!');
    }
}
