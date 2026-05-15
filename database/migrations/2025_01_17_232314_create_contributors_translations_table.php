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
        $table_name = CMSTables::ContributorsTranslations->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('contributor_id')->nullable(false)->constrained(CMSTables::Contributors->value, 'id', "{$table_name}_contributor_id_FK")->cascadeOnDelete()->comment('The contributor that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index("{$table_name}_locale_IDX")->comment('The locale of the translation');
            $table->string('slug')->nullable();
            $table->json('components')->nullable(false)->comment('The translated contributor components');

            MigrateUtils::timestamps($table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->index(['slug', 'locale'], "{$table_name}_slug_locale_IDX");
            $table->unique(['contributor_id', 'locale'], "{$table_name}_contributor_locale_UN");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::ContributorsTranslations->value);
    }
};
