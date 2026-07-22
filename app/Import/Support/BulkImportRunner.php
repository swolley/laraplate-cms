<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Modules\Core\Import\Support\BulkImportRunner as CoreBulkImportRunner;

/**
 * Generic runner wrapping a bulk import with dry-run isolation and limit helpers,
 * so individual importers do not have to reimplement transaction/rollback logic.
 */
final class BulkImportRunner
{
    private ?CoreBulkImportRunner $runner;

    public function __construct(
        ?CoreBulkImportRunner $runner = null,
    ) {
        $this->runner = $runner;
    }

    /**
     * Whether a top-level import limit has been reached.
     */
    public static function limitReached(int $imported, ?int $limit): bool
    {
        return CoreBulkImportRunner::limitReached($imported, $limit);
    }

    /**
     * Run the import. In dry-run everything happens inside a transaction that is
     * always rolled back, so mapping/validation/upserts are exercised without persisting.
     *
     * @param  callable(): int  $import  Executes the import and returns the imported count.
     */
    public function run(bool $dryRun, callable $import, ?ConnectionInterface $connection = null): int
    {
        return $this->runner()->run($dryRun, $import, $connection);
    }

    private function runner(): CoreBulkImportRunner
    {
        return $this->runner ??= new CoreBulkImportRunner(resolve(DatabaseManager::class));
    }
}
