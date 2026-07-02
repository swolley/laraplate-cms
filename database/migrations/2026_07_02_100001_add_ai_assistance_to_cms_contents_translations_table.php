<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = CMSTables::ContentsTranslations->value;

        Schema::table($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->string('ai_assistance', 32)->nullable(false)->default('none')
                ->after('components')
                ->index("{$table_name}_ai_assistance_IDX")
                ->comment('How AI assisted this locale-specific version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(CMSTables::ContentsTranslations->value, static function (Blueprint $table): void {
            $table->dropIndex(CMSTables::ContentsTranslations->value . '_ai_assistance_IDX');
            $table->dropColumn('ai_assistance');
        });
    }
};
