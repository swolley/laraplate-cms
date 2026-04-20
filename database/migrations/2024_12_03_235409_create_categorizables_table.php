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
        Schema::create('categorizables', static function (Blueprint $table): void {
            // $table->id();
            $table->unsignedBigInteger('content_id')->nullable(false)->comment('The content that the categorizable belongs to');
            $table->unsignedBigInteger('taxonomy_id')->nullable(false)->comment('The category that the categorizable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'taxonomy_id']);
            $table->foreign(['content_id'], 'categorizables_content_FK')
                ->references(['id'])
                ->on('contents')
                ->cascadeOnDelete();
            $table->foreign(['taxonomy_id'], 'categorizables_taxonomy_FK')
                ->references(['id'])
                ->on('categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorizables');
    }
};
