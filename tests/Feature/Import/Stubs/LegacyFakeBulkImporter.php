<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Feature\Import\Stubs;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Contracts\BulkImporterInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LegacyFakeBulkImporter implements BulkImporterInterface
{
    public function __construct(
        public readonly string $records = '0',
        public readonly bool $dryRun = false,
        public readonly ?int $limit = null,
    ) {}

    public function import(?OutputInterface $output = null): int
    {
        $total = max(0, (int) $this->records);

        if ($this->limit !== null && $this->limit > 0) {
            $total = min($total, $this->limit);
        }

        for ($i = 0; $i < $total; $i++) {
            $model = new FakeImportRow;
            DB::connection(config('database.default'))->table($model->getTable())->insert(['name' => "legacy-{$i}"]);
        }

        return $total;
    }
}
