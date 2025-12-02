<?php

declare(strict_types=1);

namespace Modules\Cms\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\BatchSeeder;

final class DevCmsDatabaseSeeder extends BatchSeeder
{
    private const TARGET_COUNT_AUTHORS = 15000;

    private const TARGET_COUNT_CATEGORIES = 500;

    private const TARGET_COUNT_LOCATIONS = 10000;

    private const TARGET_COUNT_TAGS = 10000;

    private const TARGET_COUNT_CONTENTS = 500000;

    protected function execute(): void
    {
        Artisan::call('module:seed', ['module' => 'Cms', '--force' => $this->command->option('force')], outputBuffer: $this->command->getOutput());

        Model::unguarded(function (): void {
            $this->seedAuthors();
            $this->seedCategories();
            $this->seedLocations();
            $this->seedTags();
            $this->seedContents();
        });

        Artisan::call('cache:clear');
    }

    private function seedAuthors(): void
    {
        $this->createInBatches(Author::class, self::TARGET_COUNT_AUTHORS);
    }

    private function seedCategories(): void
    {
        $this->createInBatches(Category::class, self::TARGET_COUNT_CATEGORIES);
    }

    private function seedLocations(): void
    {
        $this->createInBatches(Location::class, self::TARGET_COUNT_LOCATIONS);
    }

    private function seedTags(): void
    {
        $this->createInBatches(Tag::class, self::TARGET_COUNT_TAGS);
    }

    private function seedContents(): void
    {
        $this->createInBatches(Content::class, self::TARGET_COUNT_CONTENTS);

        // Create pivot relations after contents are created
        $this->createPivotRelations();
    }

    private function createPivotRelations(): void
    {
        $this->command->info('Creating pivot relations...');

        // Get all contents and create relations in batches
        Content::chunk(1000, function ($contents): void {
            foreach ($contents as $content) {
                Content::factory()->createRelations($content);
            }
        });

        $this->command->info('Pivot relations created successfully!');
    }
}
