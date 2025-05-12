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
        Schema::create('authors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('The user that the author belongs to');
            $table->string('name')->comment('The name of the author');
            $table->json('components')->nullable(false)->comment('The author contents');
            MigrateUtils::timestamps(
                $table,
                hasCreateUpdate: true,
                hasSoftDelete: true,
            );

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['user_id', 'name', 'deleted_at'], 'authors_UN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
