<?php

declare(strict_types=1);

namespace Modules\CMS\Analytics;

use Illuminate\Support\Facades\Cache;
use JsonException;
use Modules\CMS\Models\Location;

final class LocationAnalytics extends AbstractAnalytics
{
    /**
     * Inclusive min/max TTL in seconds (jitter to reduce cache stampedes).
     *
     * @var array{0: positive-int, 1: positive-int}
     */
    private static array $cache_ttl_range_seconds = [3555, 3600];

    public function __construct(private readonly Location $model) {}

    /**
     * Get location clusters.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function getLocationClusters(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('location_clusters', $filters),
            $this->cacheTtlSeconds(),
            fn (): array => $this->getGeoBasedMetrics($this->model, 'geocode', $filters),
        );
    }

    /**
     * Get content distribution by location.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function getContentDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('content_distribution', $filters),
            $this->cacheTtlSeconds(),
            function () use ($filters): array {
                $client = $this->getElasticsearchClient();

                $query = [
                    'index' => $this->model->searchableAs(),
                    'body' => [
                        'size' => 0,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['match' => ['entity' => $this->model->getTable()]],
                                ],
                            ],
                        ],
                        'aggs' => [
                            'locations' => [
                                'terms' => [
                                    'field' => 'id',
                                    'size' => 100,
                                ],
                                'aggs' => [
                                    'content_count' => [
                                        'terms' => [
                                            'field' => 'content_count',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];

                foreach ($filters as $filter_field => $value) {
                    $query['body']['query']['bool']['must'][] = ['match' => [$filter_field => $value]];
                }

                $response = $client->search($query);
                $results = $this->parseElasticsearchSearchResponse($response);

                return $this->elasticsearchAggregationBuckets($results, 'aggregations', 'locations', 'buckets');
            },
        );
    }

    /**
     * Get zone-based metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function getZoneMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('zone_metrics', $filters),
            $this->cacheTtlSeconds(),
            fn (): array => $this->getTermBasedMetrics($this->model, 'zone', $filters),
        );
    }

    /**
     * Get city-based metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function getCityMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('city_metrics', $filters),
            $this->cacheTtlSeconds(),
            fn (): array => $this->getTermBasedMetrics($this->model, 'city', $filters),
        );
    }

    public function searchableAs(): string
    {
        return $this->model->searchableAs();
    }

    public function getTable(): string
    {
        return $this->model->getTable();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function getCacheKey(string $metric, array $filters = []): string
    {
        try {
            $encoded_filters = json_encode($filters, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $encoded_filters = '';
        }

        return sprintf(
            'location_analytics:%s:%s',
            $metric,
            md5($encoded_filters),
        );
    }

    private function cacheTtlSeconds(): int
    {
        return random_int(self::$cache_ttl_range_seconds[0], self::$cache_ttl_range_seconds[1]);
    }
}
