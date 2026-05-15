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
        $table_name = CMSTables::Contents->value;
        Schema::create($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->id();
            $table->foreignId('entity_id')->nullable(false)->constrained(CoreTables::Entities->value, 'id', "{$table_name}_entity_id_FK")->cascadeOnDelete()->comment('The entity that the content belongs to');
            $table->foreignId('presettable_id')->nullable(false)->constrained(CoreTables::Presettables->value, 'id', "{$table_name}_presettable_id_FK")->cascadeOnDelete()->comment('The entity preset that the content belongs to');
            $table->json('shared_components')->nullable()->comment('The shared dynamic components of the content');
            $table->integer('order_column')->nullable(false)->default(0)->index("{$table_name}_order_column_IDX")->comment('The order of the content');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
                hasLocks: true,
                hasValidity: true,
                isValidityRequired: false,
            );

            $table->unique(['id', 'entity_id'], "{$table_name}_entity_UN");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(CMSTables::Contents->value);
    }
};
