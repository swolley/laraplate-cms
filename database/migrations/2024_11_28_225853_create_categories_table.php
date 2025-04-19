<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\CommonMigrationFunctions;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->nullable(true)->constrained('entities', 'id', 'categories_entity_id_FK')->cascadeOnDelete()->comment('The entity that the category belongs to');
            $table->unsignedBigInteger('parent_id')->nullable(true)->comment('The parent category');
            $table->unsignedBigInteger('parent_entity_id')->nullable(true)->comment('The entity that the parent category belongs to');
            $table->string('name')->nullable(false)->comment('The name of the category');
            $table->string('slug')->nullable(false)->index('categories_slug_IDX')->comment('The slug of the category');
            $table->json('components')->nullable(false)->comment('The category contents');
            $table->integer('persistence')->nullable(true)->comment('The persistence in days of the content in the category');
            $table->string('logo')->nullable(true)->comment('The logo of the category');
            $table->string('logo_full')->nullable(true)->comment('The full logo of the category');
            $table->boolean('is_active')->default(true)->nullable(false)->index('categories_is_active_IDX')->comment('Whether the category is active');
            $table->integer('order_column')->nullable(false)->default(0)->index('categories_order_column_IDX')->comment('The order of the category');
            CommonMigrationFunctions::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true,
                hasValidity: true
            );

            $table->unique(['entity_id', 'parent_id', 'name', 'deleted_at'], 'categories_name_UN');
            $table->unique(['entity_id', 'parent_id', 'slug', 'deleted_at'], 'categories_slug_UN');
            $table->unique(['id', 'parent_id'], 'category_parent_UN');
            $table->unique(['id', 'entity_id'], 'category_entity_UN');
            $table->foreign(['parent_entity_id', 'parent_id'], 'categories_parent_FK')->references(['entity_id', 'id'])->on('categories')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_parent_id_check CHECK (parent_id <> id)');
        DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_parent_entity_id_check CHECK (parent_entity_id is null OR parent_entity_id = entity_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
