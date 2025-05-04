<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Exception;
use Illuminate\Support\Carbon;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Elastic\Elasticsearch\ClientBuilder;

abstract class AbstractAnalytics
{
    /**
     * Get Elasticsearch client.
     */
    protected function getElasticsearchClient(): Client
    {
        $config = config('elastic.client.connections.default');

        $builder = ClientBuilder::create();
        $builder->setHosts($config['hosts']);

        // Imposta autenticazione se configurata
        if (! empty($config['username']) && ! empty($config['password'])) {
            $builder->setBasicAuthentication($config['username'], $config['password']);
        }

        // Imposta configurazioni di timeout
        if (! empty($config['timeout'])) {
            $builder->setRetries($config['retries'] ?? 3);
        }

        // Configura timeout tramite options
        $builder->setHttpClientOptions([
            'timeout' => $config['timeout'] ?? 60,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
        ]);

        // Imposta cloud ID se disponibile
        if (! empty($config['cloud_id'])) {
            $builder->setElasticCloudId($config['cloud_id']);
        }

        // Imposta configurazioni SSL
        if (isset($config['ssl_verification'])) {
            $builder->setSSLVerification($config['ssl_verification']);
        }

        return $builder->build();
    }

    /**
     * Get cache key based on filters.
     */
    protected function getCacheKey(string $metric, array $filters = []): string
    {
        $filters_string = ! empty($filters) ? '_' . md5(json_encode($filters)) : '';

        return 'analytics_' . $metric . $filters_string;
    }

    /**
     * Get term-based metrics from Elasticsearch.
     */
    protected function getTermBasedMetrics(Model $model, string $field, array $filters = [], int $size = 10): array
    {
        $client = $this->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'entity' => $model->getTable(),
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'by_term' => [
                        'terms' => [
                            'field' => $field,
                            'size' => $size,
                        ],
                    ],
                ],
            ],
        ];

        // Aggiungi filtri alla query se presenti
        if (! empty($filters)) {
            foreach ($filters as $filter_field => $value) {
                $query['body']['query']['bool']['must'][] = ['match' => [$filter_field => $value]];
            }
        }

        try {
            $response = $client->search($query);
            $results = $response->asArray();

            return $results['aggregations']['by_term']['buckets'] ?? [];
        } catch (Exception $e) {
            // Log dell'errore
            Log::error('Elasticsearch term-based metrics query failed', [
                'error' => $e->getMessage(),
                'field' => $field,
                'index' => $model->searchableAs(),
                'filters' => $filters,
            ]);

            return [];
        }
    }

    /**
     * Get geo-based metrics from Elasticsearch.
     */
    protected function getGeoBasedMetrics(Model $model, string $geo_field, array $filters = []): array
    {
        $client = $this->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'entity' => $model->getTable(),
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'geo_clusters' => [
                        'geohash_grid' => [
                            'field' => $geo_field,
                            'precision' => 5,
                        ],
                    ],
                ],
            ],
        ];

        // Aggiungi filtri alla query se presenti
        if (! empty($filters)) {
            foreach ($filters as $field => $value) {
                $query['body']['query']['bool']['must'][] = ['match' => [$field => $value]];
            }
        }

        try {
            $response = $client->search($query);
            $results = $response->asArray();

            return $results['aggregations']['geo_clusters']['buckets'] ?? [];
        } catch (Exception $e) {
            // Log dell'errore
            Log::error('Elasticsearch geo-based metrics query failed', [
                'error' => $e->getMessage(),
                'index' => $model->searchableAs(),
                'filters' => $filters,
            ]);

            return [];
        }
    }

    /**
     * Get distribution metrics by field.
     */
    protected function getDistributionByField(Model $model, string $field, array $filters = [], int $size = 10): array
    {
        $client = $this->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'entity' => $model->getTable(),
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'distribution' => [
                        'terms' => [
                            'field' => $field,
                            'size' => $size,
                        ],
                    ],
                ],
            ],
        ];

        // Aggiungi filtri alla query se presenti
        if (! empty($filters)) {
            foreach ($filters as $filter_field => $value) {
                $query['body']['query']['bool']['must'][] = ['match' => [$filter_field => $value]];
            }
        }

        try {
            $response = $client->search($query);
            $results = $response->asArray();

            return $results['aggregations']['distribution']['buckets'] ?? [];
        } catch (Exception $e) {
            // Log dell'errore
            Log::error('Elasticsearch distribution metrics query failed', [
                'error' => $e->getMessage(),
                'field' => $field,
                'index' => $model->searchableAs(),
                'filters' => $filters,
            ]);

            return [];
        }
    }

    /**
     * Get time-based metrics.
     */
    protected function getTimeBasedMetrics(Model $model, string $date_field, string $interval = 'day', array $filters = [], ?Carbon $start_date = null, ?Carbon $end_date = null): array
    {
        $client = $this->getElasticsearchClient();

        // Default all'ultimo mese se non specificato
        $start_date = $start_date ?? Carbon::now()->subMonth();
        $end_date = $end_date ?? Carbon::now();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            ['match' => ['entity' => $model->getTable()]],
                            [
                                'range' => [
                                    $date_field => [
                                        'gte' => $start_date->toIso8601String(),
                                        'lte' => $end_date->toIso8601String(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'aggs' => [
                    'time_series' => [
                        'date_histogram' => [
                            'field' => $date_field,
                            'calendar_interval' => $interval,
                        ],
                    ],
                ],
            ],
        ];

        // Aggiungi filtri alla query se presenti
        if (! empty($filters)) {
            foreach ($filters as $filter_field => $value) {
                $query['body']['query']['bool']['must'][] = ['match' => [$filter_field => $value]];
            }
        }

        try {
            $response = $client->search($query);
            $results = $response->asArray();

            return $results['aggregations']['time_series']['buckets'] ?? [];
        } catch (Exception $e) {
            // Log dell'errore
            Log::error('Elasticsearch time-based metrics query failed', [
                'error' => $e->getMessage(),
                'date_field' => $date_field,
                'interval' => $interval,
                'index' => $model->searchableAs(),
                'filters' => $filters,
            ]);

            return [];
        }
    }
}
