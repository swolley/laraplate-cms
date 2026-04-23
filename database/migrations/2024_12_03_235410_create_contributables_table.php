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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributables');
    }
};
