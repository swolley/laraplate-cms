<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CMS\Enums\CMSTables;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = CMSTables::Contents->value;

        Schema::table($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->string('extended_type')->nullable()->after('shared_components')
                ->index("{$table_name}_extended_type_IDX")
                ->comment('Morph alias of the module extending this content (ContentExtenderRegistry); null means a normal content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table_name = CMSTables::Contents->value;

        Schema::table($table_name, static function (Blueprint $table) use ($table_name): void {
            $table->dropIndex("{$table_name}_extended_type_IDX");
            $table->dropColumn('extended_type');
        });
    }
};
