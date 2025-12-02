<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();

            // Unique constraints for name and slug are now in tag_translations table (per locale)
            $table->string('type')->nullable()->index('tags_type_IDX')->comment('The type of the tag');
            $table->integer('order_column')->nullable(false)->default(0)->index('tags_order_column_IDX')->comment('The order of the tag');
            MigrateUtils::timestamps(
                $table,
                hasSoftDelete: true,
            );
        });

        Schema::create('taggables', function (Blueprint $table): void {
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
