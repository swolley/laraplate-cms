<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        $table_name = CMSTables::ContentsTranslations->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained(CMSTables::Contents->value, 'id', "{$table_name}_content_id_FK")->cascadeOnDelete()->comment('The content that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index("{$table_name}_locale_IDX")->comment('The locale of the translation');
            $table->string('title')->nullable(false)->comment('The translated title of the content');
            $table->string('slug')->nullable(false)->index("{$table_name}_slug_IDX")->comment('The translated slug of the content');
            $table->json('components')->nullable(false)->comment('The translated content components');
            $table->string('ai_assistance', 32)->nullable(false)->default('none')
                ->index("{$table_name}_ai_assistance_IDX")
                ->comment('How AI assisted this locale-specific version');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['content_id', 'locale'], "{$table_name}_content_locale_UN");
            $table->index(['locale', 'slug'], "{$table_name}_locale_slug_IDX");
        });

        MigrateUtils::fuzzyIndex($table_name, 'title');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::ContentsTranslations->value);
    }
};
