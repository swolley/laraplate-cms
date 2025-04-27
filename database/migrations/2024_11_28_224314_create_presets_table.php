<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->nullable(false)->constrained('entities', 'id', 'presets_entity_id_FK')->cascadeOnDelete()->comment('The entity that the preset belongs to');
            $table->string('name')->nullable(false)->comment('The name of the preset');
            $table->boolean('is_active')->default(true)->nullable(false)->index('presets_is_active_IDX')->comment('Whether the preset is active');
            $table->foreignId('template_id')->nullable(true)->constrained('templates', 'id', 'presets_template_id_FK')->cascadeOnDelete()->comment('The template that the preset belongs to');
            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['entity_id', 'name', 'deleted_at'], 'presets_UN');
            $table->unique(['entity_id', 'id'], 'presets_ids_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presets');
    }
};
