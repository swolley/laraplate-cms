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
        Schema::create('author_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('author_id')->nullable(false)->constrained('authors', 'id', 'author_translations_author_id_FK')->cascadeOnDelete()->comment('The author that the translation belongs to');
            $table->string('locale', 10)->nullable(false)->index('author_translations_locale_IDX')->comment('The locale of the translation');
            $table->string('slug')->nullable();
            $table->json('components')->nullable(false)->comment('The translated author components');
            MigrateUtils::timestamps($table);

            $table->index(['slug', 'locale'], 'author_translations_slug_locale_IDX');
            $table->unique(['author_id', 'locale'], 'author_translations_author_locale_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_translations');
    }
};
