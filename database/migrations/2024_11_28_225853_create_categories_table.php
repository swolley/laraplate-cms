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
            $table->foreignId('entity_id')->nullable(true)->constrained('entities', 'id', 'categories_entity_id_FK')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable(true);
            $table->unsignedBigInteger('parent_entity_id')->nullable(true);
            $table->string('name')->nullable(false);
            $table->string('slug')->nullable(false)->index('categories_slug_IDX');
            $table->text('description')->nullable(true);
            $table->integer('persistence')->nullable(true);
            $table->string('logo')->nullable(true);
            $table->string('logo_full')->nullable(true);
            $table->boolean('is_active')->default(true)->nullable(false)->index('categories_is_active_IDX');
            $table->integer('order_column')->nullable(false)->default(0)->index('categories_order_column_IDX');
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
            DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_parent_id_check CHECK (parent_id <> id)');
            DB::statement('ALTER TABLE categories ADD CONSTRAINT categories_parent_entity_id_check CHECK (parent_entity_id is null OR parent_entity_id = entity_id)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
