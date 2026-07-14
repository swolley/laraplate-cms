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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = CMSTables::Locations->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('place_id')
                ->nullable()
                ->constrained(CoreTables::Places->value, 'id', "{$table_name}_place_id_FK")
                ->nullOnDelete()
                ->comment('Canonical geography row in Core places');
            $table->string('name')->nullable(false)->comment('The friendly name of the location');
            $table->string('slug')->nullable(false)->comment('The slug of the location');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true,
            );

            $table->unique(['name', 'deleted_at'], "{$table_name}_name_UN");
            $table->unique(['slug', 'deleted_at'], "{$table_name}_slug_UN");
        });

        MigrateUtils::fuzzyIndex($table_name, 'name');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Locations->value);
    }
};
