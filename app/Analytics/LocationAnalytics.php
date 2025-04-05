<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Modules\Cms\Models\Location;
use Modules\Core\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;

class LocationAnalytics extends AbstractAnalytics
{
    public function __construct(private readonly Location $model) {}

    private static array $cache_duration = [3555, 3600];

    /**
     * Get location clusters
     */
    public function getLocationClusters(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('location_clusters', $filters),
            fn() => $this->getGeoBasedMetrics($this->model, 'geocode', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get content distribution by location
     */
    public function getContentDistribution(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('content_distribution', $filters),
            function () use ($filters) {
                $client = $this->model->getElasticsearchClient();

                $query = [
                    'index' => $this->model->searchableAs(),
                    'body' => [
                        'size' => 0,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    ['match' => ['entity' => $this->model->getTable()]]
                                ]
                            ]
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
                                            'field' => 'content_count'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $response = $client->search($query);
                return $response['aggregations']['locations']['buckets'];
            },
            self::$cache_duration
        );
    }

    /**
     * Get zone-based metrics
     */
    public function getZoneMetrics(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('zone_metrics', $filters),
            fn() => $this->getTermBasedMetrics($this->model, 'zone', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get city-based metrics
     */
    public function getCityMetrics(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('city_metrics', $filters),
            fn() => $this->getTermBasedMetrics($this->model, 'city', $filters),
            self::$cache_duration
        );
    }

    private function getCacheKey(string $metric, array $filters): string
    {
        return sprintf(
            'location_analytics:%s:%s',
            $metric,
            md5(json_encode($filters))
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
}
