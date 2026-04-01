<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Illuminate\Support\Facades\Cache;
use Modules\Cms\Models\Location;

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
     */
    public function getContentDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('content_distribution', $filters),
            $this->cacheTtlSeconds(),
            function () {
                $client = $this->model->getElasticsearchClient();

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

                $response = $client->search($query);

                return $response['aggregations']['locations']['buckets'];
            },
        );
    }

    /**
     * Get zone-based metrics.
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

    protected function getCacheKey(string $metric, array $filters = []): string
    {
        return sprintf(
            'location_analytics:%s:%s',
            $metric,
            md5(json_encode($filters)),
        );
    }

    private function cacheTtlSeconds(): int
    {
        return random_int(self::$cache_ttl_range_seconds[0], self::$cache_ttl_range_seconds[1]);
    }
}
