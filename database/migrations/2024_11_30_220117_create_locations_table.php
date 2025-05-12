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
            $table->string('name')->nullable(false)->fulltext('locations_name_IDX')->comment('The friendly name of the location');
            $table->string('slug')->nullable(false)->fulltext('locations_slug_IDX')->comment('The slug of the location');
            $table->string('address')->nullable(true)->comment('The address of the location');
            $table->string('city')->nullable(true)->comment('The city of the location');
            $table->string('province')->nullable(true)->comment('The province of the location');
            $table->string('country')->nullable(false)->index('locations_country_IDX')->comment('The country of the location');
            $table->string('postcode')->nullable(true)->comment('The postcode of the location');
            $table->string('zone')->nullable(true)->comment('The zone of the location');

            if (DB::connection()->getDriverName() === 'pgsql') {
                // Create PostGIS extension first
                DB::unprepared('CREATE EXTENSION IF NOT EXISTS postgis;');
                $table->geometry('geolocation', 'point', 4326)->nullable()->spatialIndex()->comment('The geolocation of the location');
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
