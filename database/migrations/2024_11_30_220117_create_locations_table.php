<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // Add fulltext indexes for databases that support them (not SQLite)
        // Add fulltext indexes for databases that support them
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE {$table_name} ADD FULLTEXT {$table_name}_name_IDX (name)");
            // DB::statement("ALTER TABLE {$table_name} ADD FULLTEXT {$table_name}_slug_IDX (slug)");
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement("CREATE INDEX {$table_name}_name_fts_idx ON {$table_name} USING gin(to_tsvector('english', name))");
            // DB::statement("CREATE INDEX {$table_name}_slug_fts_idx ON {$table_name} USING gin(to_tsvector('english', slug))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Locations->value);
    }
};
