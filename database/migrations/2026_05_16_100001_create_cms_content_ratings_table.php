<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    public function up(): void
    {
        $table_name = CMSTables::ContentRatings->value;

        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('content_id')
                ->constrained(CMSTables::Contents->value, 'id', "{$table_name}_content_id_FK")
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained(CoreTables::Users->value, 'id', "{$table_name}_user_id_FK")
                ->cascadeOnDelete();
            $table->foreignId('comment_id')
                ->nullable()
                ->constrained(CMSTables::Comments->value, 'id', "{$table_name}_comment_id_FK")
                ->nullOnDelete();
            $table->unsignedTinyInteger('score');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['content_id', 'user_id'], "{$table_name}_content_user_UN");
            $table->index(['content_id', 'score'], "{$table_name}_content_score_IDX");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(CMSTables::ContentRatings->value);
    }
};
