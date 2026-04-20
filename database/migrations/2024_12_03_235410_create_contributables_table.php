<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contributables', static function (Blueprint $table): void {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'contributables_content_id_FK')->cascadeOnDelete()->comment('The content that the contributable belongs to');
            $table->foreignId('contributor_id')->nullable(false)->constrained('contributors', 'id', 'contributables_contributor_id_FK')->cascadeOnDelete()->comment('The contributor that the contributable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'contributor_id']);
        });

        Schema::create('relatables', static function (Blueprint $table): void {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'relatables_content_id_FK')->cascadeOnDelete()->comment('The content that the relatable belongs to');
            $table->foreignId('related_content_id')->nullable(false)->constrained('contents', 'id', 'relatables_related_content_id_FK')->cascadeOnDelete()->comment('The related content that the relatable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'related_content_id']);
        });

        Schema::create('locatables', static function (Blueprint $table): void {
            // $table->id();
            $table->foreignId('content_id')->nullable(false)->constrained('contents', 'id', 'locatables_content_id_FK')->cascadeOnDelete()->comment('The content that the locatable belongs to');
            $table->foreignId('location_id')->nullable(false)->constrained('locations', 'id', 'locatables_location_id_FK')->cascadeOnDelete()->comment('The location that the locatable belongs to');
            MigrateUtils::timestamps($table);

            $table->primary(['content_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatables');
        Schema::dropIfExists('contributables');
        Schema::dropIfExists('locatables');
    }
};
