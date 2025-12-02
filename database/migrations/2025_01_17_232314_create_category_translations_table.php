<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable(false)->constrained('categories', 'id', 'category_translations_category_id_FK')->cascadeOnDelete()->comment('The category that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('category_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('name')->nullable(false)->comment('The translated name of the category');
            $table->string('slug')->nullable(false)->index('category_translations_slug_IDX')->comment('The translated slug of the category');
            $table->json('components')->nullable(false)->comment('The translated category components');
            MigrateUtils::timestamps($table);

            $table->unique(['category_id', 'locale'], 'category_translations_category_locale_UN');
            $table->index(['locale', 'slug'], 'category_translations_locale_slug_IDX');
        });

        // Add fulltext indexes for databases that support them
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE category_translations ADD FULLTEXT category_translations_name_IDX (name)');
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement('CREATE INDEX category_translations_name_fts_idx ON category_translations USING gin(to_tsvector(\'english\', name))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
