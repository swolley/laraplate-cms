<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Helpers\CommonMigrationFunctions;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false)->fulltext('locations_name_IDX');
            $table->string('slug')->nullable(false);
            $table->string('address')->nullable(true);
            $table->string('city')->nullable(true);
            $table->string('province')->nullable(true);
            $table->string('country')->nullable(false)->index('locations_country_IDX');
            $table->string('postcode')->nullable(true);
            $table->string('zone')->nullable(true);

            if (DB::connection()->getDriverName() === 'pgsql') {
                // Create PostGIS extension first
                DB::unprepared('CREATE EXTENSION IF NOT EXISTS postgis;');
                $table->geometry('geolocation', 'point', 4326)->nullable()->spatialIndex();
            } else {
                $table->geometry('geolocation')->nullable()->spatialIndex();
            }

            CommonMigrationFunctions::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true
            );

            $table->unique(['name', 'deleted_at'], 'locations_name_UN');
            $table->unique(['slug', 'deleted_at'], 'locations_slug_UN');
            // if (in_array(DB::connection()->getDriverName(), ['oracle', 'pgsql'])) {
            $table->index('city', 'locations_city_IDX');
            $table->index('province', 'locations_province_IDX');
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
