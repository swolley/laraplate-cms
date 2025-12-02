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
        Schema::create('tag_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->nullable(false)->constrained('tags', 'id', 'tag_translations_tag_id_FK')->cascadeOnDelete()->comment('The tag that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('tag_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('name')->nullable(false)->comment('The translated name of the tag');
            $table->string('slug')->nullable(false)->index('tag_translations_slug_IDX')->comment('The translated slug of the tag');
            MigrateUtils::timestamps($table);

            $table->unique(['tag_id', 'locale'], 'tag_translations_tag_locale_UN');
            $table->index(['locale', 'slug'], 'tag_translations_locale_slug_IDX');
        });

        // Add fulltext indexes for databases that support them
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tag_translations ADD FULLTEXT tag_translations_name_IDX (name)');
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement('CREATE INDEX tag_translations_name_fts_idx ON tag_translations USING gin(to_tsvector(\'english\', name))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
