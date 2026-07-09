<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Feature\Import\Stubs;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Contracts\BulkImporterInterface;

/**
 * In-memory importer used to exercise the generic cms:import command:
 * it records the constructor arguments it received and inserts N rows
 * into a scratch table so dry-run rollback and limit can be asserted.
 */
final class FakeBulkImporter implements BulkImporterInterface
{
    public const string TABLE = 'fake_import_rows';

    /**
     * @var array<string, mixed>
     */
    public static array $lastArguments = [];

    public function __construct(
        public readonly string $records = '0',
        public readonly bool $dryRun = false,
        public readonly ?int $limit = null,
    ) {}

    public function import(): int
    {
        self::$lastArguments = [
            'records' => $this->records,
            'dryRun' => $this->dryRun,
            'limit' => $this->limit,
        ];

        $total = max(0, (int) $this->records);

        if ($this->limit !== null && $this->limit > 0) {
            $total = min($total, $this->limit);
        }

        for ($i = 0; $i < $total; $i++) {
            DB::table(self::TABLE)->insert(['name' => "row-{$i}"]);
        }

        return $total;
    }
}
