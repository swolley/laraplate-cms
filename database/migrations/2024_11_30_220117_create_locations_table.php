<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable(false)->comment('The friendly name of the location');
            $table->string('slug')->nullable(false)->comment('The slug of the location');
            $table->string('address')->nullable(true)->comment('The address of the location');
            $table->string('city')->nullable(true)->comment('The city of the location');
            $table->string('province')->nullable(true)->comment('The province of the location');
            $table->string('country')->nullable(false)->index('locations_country_IDX')->comment('The country of the location');
            $table->string('postcode')->nullable(true)->comment('The postcode of the location');
            $table->string('zone')->nullable(true)->comment('The zone of the location');

            $driver = DB::connection()->getDriverName();

            if ($driver === 'pgsql') {
                // Create PostGIS extension first
                DB::unprepared('CREATE EXTENSION IF NOT EXISTS postgis;');
                $table->geometry('geolocation', 'point', 4326)->nullable()->spatialIndex()->comment('The geolocation of the location');
            } elseif ($driver === 'sqlite') {
                // SQLite doesn't support spatial indexes, just store as text
                $table->text('geolocation')->nullable()->comment('The geolocation of the location (JSON for SQLite)');
            } else {
                $table->geometry('geolocation')->nullable()->spatialIndex()->comment('The geolocation of the location');
            }

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true,
            );

            $table->unique(['name', 'deleted_at'], 'locations_name_UN');
            $table->unique(['slug', 'deleted_at'], 'locations_slug_UN');
            // if (in_array(DB::connection()->getDriverName(), ['oracle', 'pgsql'])) {
            $table->index('city', 'locations_city_IDX');
            $table->index('province', 'locations_province_IDX');
            // }
        });

        // Add fulltext indexes for databases that support them (not SQLite)
        // Add fulltext indexes for databases that support them
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE locations ADD FULLTEXT locations_name_IDX (name)');
            DB::statement('ALTER TABLE locations ADD FULLTEXT locations_slug_IDX (slug)');
        } elseif (DB::getDriverName() === 'pgsql') {
            // PostgreSQL fulltext search indexes
            // TODO: This is temporary fixed to english for now
            DB::statement('CREATE INDEX locations_name_fts_idx ON locations USING gin(to_tsvector(\'english\', name))');
            DB::statement('CREATE INDEX locations_slug_fts_idx ON locations USING gin(to_tsvector(\'english\', slug))');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
