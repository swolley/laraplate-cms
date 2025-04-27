<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique('tags_name_UN')->comment('The name of the tag');
            $table->string('slug')->unique('tags_slug_UN')->comment('The slug of the tag');
            $table->string('type')->nullable()->index('tags_type_IDX')->comment('The type of the tag');
            $table->integer('order_column')->nullable(false)->default(0)->index('tags_order_column_IDX')->comment('The order of the tag');
            MigrateUtils::timestamps(
                $table,
                hasSoftDelete: true
            );

            $table->index(['name', 'deleted_at'], 'tags_name_deleted_at_idx');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags', 'id', 'taggables_tag_id_FK')->cascadeOnDelete()->comment('The tag that the taggable belongs to');
            $table->morphs('taggable', 'taggables_morph_idx');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_primary_idx');
            $table->index(['taggable_type', 'taggable_id'], 'taggables_inverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
