<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', static function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained('tags', 'id', 'taggables_tag_id_FK')->cascadeOnDelete()->comment('The tag that the taggable belongs to');
            $table->morphs('taggable', 'taggables_morph_idx');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_primary_idx');
            $table->index(['taggable_type', 'taggable_id'], 'taggables_inverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
