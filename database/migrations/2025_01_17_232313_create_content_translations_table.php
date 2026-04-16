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
        Schema::create('contents_translations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'contents_translations_content_id_FK')->cascadeOnDelete()->comment('The content that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('contents_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('title')->nullable(false)->comment('The translated title of the content');
            $table->string('slug')->nullable(false)->index('contents_translations_slug_IDX')->comment('The translated slug of the content');
            $table->json('components')->nullable(false)->comment('The translated content components');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['content_id', 'locale'], 'contents_translations_content_locale_UN');
            $table->index(['locale', 'slug'], 'contents_translations_locale_slug_IDX');
        });

        // Add fulltext indexes for databases that support them
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE contents_translations ADD FULLTEXT contents_translations_title_IDX (title)');
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement('CREATE INDEX contents_translations_title_fts_idx ON contents_translations USING gin(to_tsvector(\'english\', title))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents_translations');
    }
};
