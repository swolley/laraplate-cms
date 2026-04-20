<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Helpers\MigrateUtils;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', static function (Blueprint $table): void {
            $table->id();

            // Unique constraints for name and slug are now in tags_translations table (per locale)
            $table->string('type')->nullable()->index('tags_type_IDX')->comment('The type of the tag');
            $table->integer('order_column')->nullable(false)->default(0)->index('tags_order_column_IDX')->comment('The order of the tag');

            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
