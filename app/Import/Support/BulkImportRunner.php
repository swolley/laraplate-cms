<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generic runner wrapping a bulk import with dry-run isolation and limit helpers,
 * so individual importers do not have to reimplement transaction/rollback logic.
 */
final class BulkImportRunner
{
    /**
     * Run the import. In dry-run everything happens inside a transaction that is
     * always rolled back, so mapping/validation/upserts are exercised without persisting.
     *
     * @param  callable(): int  $import  Executes the import and returns the imported count.
     */
    public function run(bool $dryRun, callable $import): int
    {
        if (! $dryRun) {
            return $import();
        }

        DB::beginTransaction();

        try {
            return $import();
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Whether a top-level import limit has been reached.
     */
    public static function limitReached(int $imported, ?int $limit): bool
    {
        return $limit !== null && $limit > 0 && $imported >= $limit;
    }
}
