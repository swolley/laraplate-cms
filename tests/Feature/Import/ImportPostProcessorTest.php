<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Support\ImportPostProcessor;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Run the post-import reindex on the given Scout driver and return the queries
 * it sent to the contents table.
 *
 * @return list<string>
 */
function contentQueriesDuringPostProcess(string $driver): array
{
    config(['scout.driver' => $driver]);
    $table = (new Content)->getTable();
    $queries = [];

    DB::listen(static function (QueryExecuted $query) use (&$queries, $table): void {
        if (str_contains($query->sql, $table)) {
            $queries[] = $query->sql;
        }
    });

    (new ImportPostProcessor)->run(clearCaches: false, reindex: true);

    return $queries;
}

it('skips the post-import reindex on the null search driver', function (): void {
    $queries = contentQueriesDuringPostProcess('null');

    expect($queries)->toBe([]);
});

it('runs the post-import reindex on a real search driver', function (): void {
    $queries = contentQueriesDuringPostProcess('collection');

    expect($queries)->not->toBe([]);
});
