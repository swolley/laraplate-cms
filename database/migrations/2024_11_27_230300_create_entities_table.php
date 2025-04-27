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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false)->comment('The name of the entity')->unique('entities_name_UN');
            $table->string('slug')->nullable(false)->comment('The slug of the entity')->unique('entities_slug_UN');
            $table->string('type')->nullable(false)->index('entities_type_IDX')->comment('The type of the entity');
            $table->boolean('is_active')->default(true)->nullable(false)->index('entities_is_active_IDX')->comment('Whether the entity is active');
            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasLocks: true
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
