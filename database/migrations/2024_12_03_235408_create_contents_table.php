<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->nullable(false)->constrained('entities', 'id', 'contents_entity_id_FK')->cascadeOnDelete()->comment('The entity that the content belongs to');
            $table->unsignedBigInteger('preset_id')->nullable(false)->comment('The preset that the content belongs to');
            $table->string('title')->nullable(false)->fullText('contents_title_IDX')->comment('The title of the content');
            $table->json('components')->nullable(false)->comment('The content contents');
            $table->string('slug')->nullable(false)->index('contents_slug_IDX')->comment('The slug of the content');
            $table->integer('order_column')->nullable(false)->default(0)->index('contents_order_column_IDX')->comment('The order of the content');
            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true,
                hasValidity: true,
                isValidityRequired: false
            );

            $table->foreign(['entity_id', 'preset_id'], 'contents_preset_FK')
                ->references(['entity_id', 'id'])
                ->on('presets')
                ->cascadeOnDelete();
            $table->unique(['id', 'entity_id'], 'content_entity_UN');
        });

        Schema::create('categorizables', function (Blueprint $table) {
            // $table->id();
            $table->unsignedBigInteger('content_id')->nullable(false)->comment('The content that the categorizable belongs to');
            $table->unsignedBigInteger('category_id')->nullable(false)->comment('The category that the categorizable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'category_id']);
            $table->foreign(['content_id'], 'categorizables_content_FK')
                ->references(['id'])
                ->on('contents')
                ->cascadeOnDelete();
            $table->foreign(['category_id'], 'categorizables_category_FK')
                ->references(['id'])
                ->on('categories')
                ->cascadeOnDelete();
        });

        Schema::create('authorables', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'authorables_content_id_FK')->cascadeOnDelete()->comment('The content that the authorable belongs to');
            $table->foreignId('author_id')->nullable(false)->constrained('authors', 'id', 'authorables_author_id_FK')->cascadeOnDelete()->comment('The author that the authorable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'author_id']);
        });

        Schema::create('relatables', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'relatables_content_id_FK')->cascadeOnDelete()->comment('The content that the relatable belongs to');
            $table->foreignId('related_content_id')->nullable(false)->constrained('contents', 'id', 'relatables_related_content_id_FK')->cascadeOnDelete()->comment('The related content that the relatable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'related_content_id']);
        });

        Schema::create('locatables', function (Blueprint $table) {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'locatables_content_id_FK')->cascadeOnDelete()->comment('The content that the locatable belongs to');
            $table->foreignId('location_id')->nullable(false)->constrained('locations', 'id', 'locatables_location_id_FK')->cascadeOnDelete()->comment('The location that the locatable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatables');
        Schema::dropIfExists('authorables');
        Schema::dropIfExists('categorizables');
        Schema::dropIfExists('locatables');
        Schema::dropIfExists('contents');
    }
};
