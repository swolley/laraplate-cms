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
        Schema::create('locations', static function (Blueprint $table): void {
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
            } elseif (in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
                // Campo opzionale, nessun spatial index per evitare NOT NULL forzato
                $table->geometry('geolocation')->nullable()->comment('The geolocation of the location');
            } elseif ($driver === 'oracle') {
                // Campo opzionale; registriamo metadata SDO e creiamo indice spaziale
                $table->geometry('geolocation')->nullable()->comment('The geolocation of the location');

                DB::unprepared("
                    DECLARE
                        tbl VARCHAR2(128) := 'LOCATIONS';
                        col VARCHAR2(128) := 'GEOLOCATION';
                        srid NUMBER := 4326;
                    BEGIN
                        BEGIN
                            DELETE FROM user_sdo_geom_metadata WHERE table_name = tbl AND column_name = col;
                        EXCEPTION
                            WHEN NO_DATA_FOUND THEN NULL;
                        END;

                        INSERT INTO user_sdo_geom_metadata (table_name, column_name, diminfo, srid)
                        VALUES (
                            tbl,
                            col,
                            MDSYS.SDO_DIM_ARRAY(
                                MDSYS.SDO_DIM_ELEMENT('LONG', -180, 180, 0.005),
                                MDSYS.SDO_DIM_ELEMENT('LAT', -90, 90, 0.005)
                            ),
                            srid
                        );
                    END;
                ");

                DB::unprepared('CREATE INDEX locations_geolocation_spx ON locations(geolocation) INDEXTYPE IS MDSYS.SPATIAL_INDEX');
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
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
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
