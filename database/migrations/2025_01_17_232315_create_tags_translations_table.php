<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = CMSTables::TagsTranslations->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('tag_id')->nullable(false)->constrained(CMSTables::Tags->value, 'id', "{$table_name}_tag_id_FK")->cascadeOnDelete()->comment('The tag that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index("{$table_name}_locale_IDX")->comment('The locale of the translation');
            $table->string('name')->nullable(false)->comment('The translated name of the tag');
            $table->string('slug')->nullable(false)->index("{$table_name}_slug_IDX")->comment('The translated slug of the tag');

            MigrateUtils::timestamps($table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['tag_id', 'locale'], "{$table_name}_tag_locale_UN");
            $table->index(['locale', 'slug'], "{$table_name}_locale_slug_IDX");
        });

        // Add fulltext indexes for databases that support them
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE {$table_name} ADD FULLTEXT {$table_name}_name_IDX (name)");
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement("CREATE INDEX {$table_name}_name_fts_idx ON {$table_name} USING gin(to_tsvector('english', name))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::TagsTranslations->value);
    }
};
