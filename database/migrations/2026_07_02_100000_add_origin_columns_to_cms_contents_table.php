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

        Schema::table($table_name, static function (Blueprint $table): void {
            $table->string('origin_label')->nullable()->after('shared_components')
                ->comment('Human-readable name of the external origin source');
            $table->string('origin_url', 2048)->nullable()->after('origin_label')
                ->comment('Link to the external origin source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(CMSTables::Contents->value, static function (Blueprint $table): void {
            $table->dropColumn(['origin_label', 'origin_url']);
        });
    }
};
