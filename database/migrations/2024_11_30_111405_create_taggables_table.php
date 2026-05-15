<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;

return new class extends Migration
{
    public function up(): void
    {
        $table_name = CMSTables::Taggables->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->foreignId('tag_id')->constrained(CMSTables::Tags->value, 'id', "{$table_name}_tag_id_FK")->cascadeOnDelete()->comment('The tag that the taggable belongs to');
            $table->morphs('taggable', 'taggables_morph_idx');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], "{$table_name}_primary_idx");
            $table->index(['taggable_type', 'taggable_id'], "{$table_name}_inverse_idx");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Taggables->value);
    }
};
