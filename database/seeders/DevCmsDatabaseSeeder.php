<?php

namespace Modules\Cms\Database\Seeders;

use Modules\Cms\Models\Tag;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Location;
use Modules\Core\Helpers\BatchSeeder;

class DevCmsDatabaseSeeder extends BatchSeeder
{
    private const TARGET_COUNT_AUTHORS = 2000;
    private const TARGET_COUNT_CATEGORIES = 500;
    private const TARGET_COUNT_LOCATIONS = 1000;
    private const TARGET_COUNT_TAGS = 10000;
    private const TARGET_COUNT_CONTENTS = 100000;

    protected function execute(): void
    {
        $this->seedAuthors();
        $this->seedCategories();
        $this->seedLocations();
        $this->seedTags();
        $this->seedContents();
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
    }
}
