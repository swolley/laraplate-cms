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
        Schema::create('contributors', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('The user that the contributor belongs to');
            $table->foreignId('entity_id')->nullable(false)->constrained('entities', 'id', 'contributors_entity_id_FK')->cascadeOnDelete()->comment('The entity that the contributor belongs to');
            $table->foreignId('presettable_id')->nullable(false)->constrained('presettables', 'id', 'contributors_presettable_id_FK')->cascadeOnDelete()->comment('The entity preset that the contributor belongs to');
            $table->json('shared_components')->nullable()->comment('The shared dynamic components of the contributor');
            $table->string('name')->comment('The name of the contributor');
            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['user_id', 'name', 'deleted_at'], 'contributors_UN');
            $table->unique(['id', 'entity_id'], 'contributor_entity_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributors');
    }
};
