<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Modules\Cms\Models\Location;
use Illuminate\Support\Facades\Cache;

final class LocationAnalytics extends AbstractAnalytics
{
    private static array $cache_duration = [3555, 3600];

    public function __construct(private readonly Location $model) {}

    /**
     * Get location clusters.
     */
    public function getLocationClusters(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('location_clusters', $filters),
            self::$cache_duration,
            fn () => $this->getGeoBasedMetrics($this->model, 'geocode', $filters),
        );
    }

    /**
     * Get content distribution by location.
     */
    public function getContentDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('content_distribution', $filters),
            self::$cache_duration,
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
            self::$cache_duration,
            fn () => $this->getTermBasedMetrics($this->model, 'zone', $filters),
        );
    }

    /**
     * Get city-based metrics.
     */
    public function getCityMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('city_metrics', $filters),
            self::$cache_duration,
            fn () => $this->getTermBasedMetrics($this->model, 'city', $filters),
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
}
