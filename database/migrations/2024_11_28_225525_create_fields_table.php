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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false)->unique();
            $table->string('type')->nullable(false);
            $table->json('options')->nullable(false);
            $table->boolean('is_slug')->default(false)->nullable(false);
            $table->boolean('is_active')->default(true)->nullable(false)->index('fields_is_active_IDX');
            CommonMigrationFunctions::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->unique(['name', 'deleted_at'], 'fields_name_UN');
        });

        Schema::create('fieldables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preset_id')->nullable(false)->constrained('presets', 'id', 'fieldables_preset_id_FK')->cascadeOnDelete();
            $table->foreignId('field_id')->nullable(false)->constrained('fields', 'id', 'fieldables_field_id_FK')->cascadeOnDelete();
            $table->boolean('is_required')->default(false)->nullable(false);
            $table->integer('order_column')->nullable(false)->default(0)->index('fieldables_order_column_IDX');
            $table->json('default')->nullable(true);
            CommonMigrationFunctions::timestamps($table);

            $table->unique(['preset_id', 'field_id'], 'fieldables_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
