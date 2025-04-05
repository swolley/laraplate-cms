<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\CommonMigrationFunctions;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique('tags_name_UN');
            $table->string('slug')->unique('tags_slug_UN');
            $table->string('type')->nullable()->index('tags_type_IDX');
            $table->integer('order_column')->nullable(false)->default(0)->index('tags_order_column_IDX');
            CommonMigrationFunctions::timestamps(
                $table,
                hasSoftDelete: true
            );

            $table->index(['name', 'deleted_at'], 'tags_name_deleted_at_idx');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags', 'id', 'taggables_tag_id_FK')->cascadeOnDelete();
            $table->morphs('taggable');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
            $table->index(['taggable_type', 'taggable_id'], 'taggables_inverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
