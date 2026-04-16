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
        Schema::create('tags_translations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->nullable(false)->constrained('tags', 'id', 'tags_translations_tag_id_FK')->cascadeOnDelete()->comment('The tag that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('tags_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('name')->nullable(false)->comment('The translated name of the tag');
            $table->string('slug')->nullable(false)->index('tags_translations_slug_IDX')->comment('The translated slug of the tag');

            MigrateUtils::timestamps($table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['tag_id', 'locale'], 'tags_translations_tag_locale_UN');
            $table->index(['locale', 'slug'], 'tags_translations_locale_slug_IDX');
        });

        // Add fulltext indexes for databases that support them
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE tags_translations ADD FULLTEXT tags_translations_name_IDX (name)');
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement('CREATE INDEX tags_translations_name_fts_idx ON tags_translations USING gin(to_tsvector(\'english\', name))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags_translations');
    }
};
