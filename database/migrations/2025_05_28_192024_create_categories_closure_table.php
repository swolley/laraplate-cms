<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories_closure', function (Blueprint $table): void {
            $table->unsignedBigInteger('ancestor_id');
            $table->unsignedBigInteger('descendant_id');
            $table->unsignedInteger('depth');

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->foreign('ancestor_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('descendant_id')->references('id')->on('categories')->onDelete('cascade');

            $table->index(['ancestor_id', 'depth'], 'categories_closure_ancestor_depth_idx');
            $table->index(['descendant_id', 'depth'], 'categories_closure_descendant_depth_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_closure');
    }
};
