<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $default_locale = config('app.locale');

        // Migrate contents data
        if (Schema::hasTable('contents') && Schema::hasColumn('contents', 'title')) {
            $contents = DB::table('contents')->whereNotNull('title')->get();

            foreach ($contents as $content) {
                DB::table('content_translations')->insert([
                    'content_id' => $content->id,
                    'locale' => $default_locale,
                    'title' => $content->title,
                    'slug' => $content->slug ?? '',
                    'components' => $content->components ?? json_encode([]),
                    'created_at' => $content->created_at ?? now(),
                    'updated_at' => $content->updated_at ?? now(),
                ]);
            }
        }

        // Migrate categories data
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'name')) {
            $categories = DB::table('categories')->whereNotNull('name')->get();

            foreach ($categories as $category) {
                DB::table('category_translations')->insert([
                    'category_id' => $category->id,
                    'locale' => $default_locale,
                    'name' => $category->name,
                    'slug' => $category->slug ?? '',
                    'components' => $category->components ?? json_encode([]),
                    'created_at' => $category->created_at ?? now(),
                    'updated_at' => $category->updated_at ?? now(),
                ]);
            }
        }

        // Migrate tags data
        if (Schema::hasTable('tags') && Schema::hasColumn('tags', 'name')) {
            $tags = DB::table('tags')->whereNotNull('name')->get();

            foreach ($tags as $tag) {
                DB::table('tag_translations')->insert([
                    'tag_id' => $tag->id,
                    'locale' => $default_locale,
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? '',
                    'created_at' => $tag->created_at ?? now(),
                    'updated_at' => $tag->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we cannot restore original data structure
        // The original columns have been removed in previous migrations
    }
};
