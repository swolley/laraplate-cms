<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Schema\Blueprint;

final class MigrateUtils
{
    public static function timestamps(
        Blueprint $table,
        bool $hasCreateUpdate = true,
        bool $hasSoftDelete = false,
        bool $hasLocks = false,
        bool $hasValidity = false,
        bool $isValidityRequired = false,
    ): void {
        if ($hasCreateUpdate) {
            $table->timestamps();
        }

        if ($hasSoftDelete) {
            $table->softDeletes();
        }

        if ($hasLocks) {
            $table->unsignedBigInteger('lock_version')->default(0);
        }

        if ($hasValidity) {
            $table->timestamp('valid_from')->nullable(! $isValidityRequired);
            $table->timestamp('valid_to')->nullable();
        }
    }
}
