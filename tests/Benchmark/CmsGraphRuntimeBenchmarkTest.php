<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

function cmsGraphBenchmarkEnabled(): bool
{
    return filter_var(env('CMS_GRAPH_BENCHMARK_ENABLED', false), FILTER_VALIDATE_BOOL);
}

function cmsGraphBenchmarkInt(string $key, int $default): int
{
    return max(1, (int) env($key, $default));
}

/**
 * @return array{contents: int, tags: int, categories: int, contributors: int, locations: int, iterations: int}
 */
function cmsGraphBenchmarkConfig(): array
{
    return [
        'contents' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_CONTENTS', 250),
        'tags' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_TAGS', 80),
        'categories' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_CATEGORIES', 40),
        'contributors' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_CONTRIBUTORS', 40),
        'locations' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_LOCATIONS', 40),
        'iterations' => cmsGraphBenchmarkInt('CMS_GRAPH_BENCHMARK_ITERATIONS', 5),
    ];
}

it('measures cms graph runtime traversal on a realistic dataset', function (): void {
    if (! cmsGraphBenchmarkEnabled()) {
        $this->markTestSkipped('Set CMS_GRAPH_BENCHMARK_ENABLED=true to run the CMS graph runtime benchmark.');
    }

    Config::set('core.expose_crud_api', true);
    setupCMSEntities();

    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('superadmin', 'web'));

    $config = cmsGraphBenchmarkConfig();

    $tags = Tag::factory()->count($config['tags'])->create();
    $categories = Category::factory()->count($config['categories'])->create();
    $contributors = Contributor::factory()->count($config['contributors'])->create();
    $locations = Location::factory()->count($config['locations'])->create();

    $contents = Content::factory()
        ->count($config['contents'])
        ->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    $contents->each(function (Content $content) use ($tags, $categories, $contributors, $locations): void {
        $content->tags()->syncWithoutDetaching($tags->random(min($tags->count(), random_int(3, 8)))->modelKeys());
        $content->categories()->syncWithoutDetaching($categories->random(min($categories->count(), random_int(1, 3)))->modelKeys());
        $content->contributors()->syncWithoutDetaching($contributors->random(min($contributors->count(), random_int(1, 5)))->modelKeys());
        $content->locations()->syncWithoutDetaching($locations->random(min($locations->count(), random_int(1, 2)))->modelKeys());
    });

    $center = $contents->firstOrFail();
    $searchQuery = (string) $center->title;

    $scenarios = [
        'expand_provider_defaults' => '/api/v1/crud/graph/expand/CMS/contents/' . $center->getKey(),
        'expand_two_relations' => '/api/v1/crud/graph/expand/CMS/contents/' . $center->getKey() . '?relations[]=tags&relations[]=categories',
        'expand_four_relations' => '/api/v1/crud/graph/expand/CMS/contents/' . $center->getKey() . '?relations[]=tags&relations[]=categories&relations[]=contributors&relations[]=locations',
        'search_nodes_only' => '/api/v1/crud/graph/search/CMS/contents?qs=' . urlencode($searchQuery) . '&limit=20',
        'search_expanded' => '/api/v1/crud/graph/search/CMS/contents?qs=' . urlencode($searchQuery) . '&limit=20&relations[]=tags&relations[]=categories',
        'stats_two_relations' => '/api/v1/crud/graph/stats/CMS/contents/' . $center->getKey() . '?relations[]=tags&relations[]=categories',
    ];

    $metrics = [];

    foreach ($scenarios as $name => $uri) {
        $metrics[$name] = cmsGraphBenchmarkScenario($this, $user, $uri, $config['iterations']);
    }

    fwrite(STDERR, PHP_EOL . json_encode([
        'dataset' => $config,
        'metrics' => $metrics,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);

    expect($metrics)->not->toBeEmpty();
})->group('benchmark', 'graph', 'cms');

/**
 * @return array<string, mixed>
 */
function cmsGraphBenchmarkScenario(TestCase $test, User $user, string $uri, int $iterations): array
{
    $samples = [];

    for ($i = 0; $i < $iterations; $i++) {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = hrtime(true);
        $startMemory = memory_get_usage(true);

        $response = $test->actingAs($user)->getJson($uri);

        $durationMs = (hrtime(true) - $start) / 1_000_000;
        $peakMemoryMb = (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024;
        $queries = count(DB::getQueryLog());
        DB::flushQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $payload = $response->json('data') ?? [];

        $samples[] = [
            'duration_ms' => round($durationMs, 2),
            'queries' => $queries,
            'peak_memory_mb' => round(max(0, $peakMemoryMb), 2),
            'nodes' => is_countable($payload['nodes'] ?? null) ? count($payload['nodes']) : 0,
            'edges' => is_countable($payload['edges'] ?? null) ? count($payload['edges']) : 0,
            'truncated' => (bool) data_get($payload, 'graphMeta.truncated', false),
            'filtered_by_acl' => (bool) data_get($payload, 'graphMeta.filteredByAcl', false),
        ];
    }

    return [
        'iterations' => $iterations,
        'duration_ms' => cmsGraphBenchmarkStats(array_column($samples, 'duration_ms')),
        'queries' => cmsGraphBenchmarkStats(array_column($samples, 'queries')),
        'peak_memory_mb' => cmsGraphBenchmarkStats(array_column($samples, 'peak_memory_mb')),
        'last_nodes' => $samples[array_key_last($samples)]['nodes'],
        'last_edges' => $samples[array_key_last($samples)]['edges'],
        'truncated' => $samples[array_key_last($samples)]['truncated'],
        'filtered_by_acl' => $samples[array_key_last($samples)]['filtered_by_acl'],
    ];
}

/**
 * @param  list<int|float>  $values
 * @return array{min: float, avg: float, p95: float, max: float}
 */
function cmsGraphBenchmarkStats(array $values): array
{
    sort($values);

    $count = count($values);
    $p95Index = max(0, (int) ceil($count * 0.95) - 1);

    return [
        'min' => round((float) $values[0], 2),
        'avg' => round((float) array_sum($values) / $count, 2),
        'p95' => round((float) $values[$p95Index], 2),
        'max' => round((float) $values[$count - 1], 2),
    ];
}
