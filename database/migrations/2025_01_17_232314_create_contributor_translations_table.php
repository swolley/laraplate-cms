<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contributors_translations', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contributor_id')->nullable(false)->constrained('contributors', 'id', 'contributors_translations_contributor_id_FK')->cascadeOnDelete()->comment('The contributor that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('contributors_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('slug')->nullable();
            $table->json('components')->nullable(false)->comment('The translated contributor components');

            MigrateUtils::timestamps($table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->index(['slug', 'locale'], 'contributors_translations_slug_locale_IDX');
            $table->unique(['contributor_id', 'locale'], 'contributors_translations_contributor_locale_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributors_translations');
    }
};
