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
        $table_name = CMSTables::Contributors->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('The user that the contributor belongs to');
            $table->foreignId('entity_id')->nullable(false)->constrained(CoreTables::Entities->value, 'id', "{$table_name}_entity_id_FK")->cascadeOnDelete()->comment('The entity that the contributor belongs to');
            $table->foreignId('presettable_id')->nullable(false)->constrained(CoreTables::Presettables->value, 'id', "{$table_name}_presettable_id_FK")->cascadeOnDelete()->comment('The entity preset that the contributor belongs to');
            $table->json('shared_components')->nullable()->comment('The shared dynamic components of the contributor');
            $table->string('name')->comment('The name of the contributor');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->foreign('user_id')->references('id')->on(CoreTables::Users->value)->onDelete('set null');
            $table->unique(['user_id', 'name', 'deleted_at'], "{$table_name}_UN");
            $table->unique(['id', 'entity_id'], "{$table_name}_entity_UN");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Contributors->value);
    }
};
