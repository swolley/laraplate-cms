<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = CMSTables::ContentsReferences->value;

        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('content_id')->nullable(false)
                ->constrained(CMSTables::Contents->value, 'id', "{$table_name}_content_id_FK")
                ->cascadeOnDelete()
                ->comment('The content that the reference belongs to');
            $table->string('label')->nullable(false)->comment('Human-readable name of the source');
            $table->string('url', 2048)->nullable()->comment('Optional link to the source');
            $table->integer('order_column')->nullable(false)->default(0)
                ->index("{$table_name}_order_column_IDX")
                ->comment('Display order of the reference');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->index(['content_id', 'order_column'], "{$table_name}_content_order_IDX");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::ContentsReferences->value);
    }
};
