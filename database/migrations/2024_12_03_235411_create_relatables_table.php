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
        $table_name = CMSTables::Relatables->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained(CMSTables::Contents->value, 'id', "{$table_name}_content_id_FK")->cascadeOnDelete()->comment('The content that the relatable belongs to');
            $table->foreignId('related_content_id')->nullable(false)->constrained(CMSTables::Contents->value, 'id', "{$table_name}_related_content_id_FK")->cascadeOnDelete()->comment('The related content that the relatable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'related_content_id'], "{$table_name}_primary_idx");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Relatables->value);
    }
};
