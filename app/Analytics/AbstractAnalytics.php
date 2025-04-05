<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractAnalytics
{
    /**
     * Get time-based aggregations from Elasticsearch
     */
    protected function getTimeBasedMetrics(Model $model, array $filters = [], string $interval = '1M'): array
    {
        $client = $model->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            ['match' => ['entity' => $model->getTable()]]
                        ]
                    ]
                ],
                'aggs' => [
                    'over_time' => [
                        'date_histogram' => [
                            'field' => 'valid_from',
                            'calendar_interval' => $interval
                        ]
                    ]
                ]
            ]
        ];

        // Aggiungi filtri se presenti
        if (!empty($filters['date_from'])) {
            $query['body']['query']['bool']['must'][] = [
                'range' => [
                    'valid_from' => [
                        'gte' => $filters['date_from']
                    ]
                ]
            ];
        }

        if (!empty($filters['date_to'])) {
            $query['body']['query']['bool']['must'][] = [
                'range' => [
                    'valid_from' => [
                        'lte' => $filters['date_to']
                    ]
                ]
            ];
        }

        $response = $client->search($query);
        return $response['aggregations']['over_time']['buckets'];
    }

    /**
     * Get term-based aggregations from Elasticsearch
     */
    protected function getTermBasedMetrics(Model $model, string $field, array $filters = [], int $size = 10): array
    {
        $client = $model->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            ['match' => ['entity' => $model->getTable()]]
                        ]
                    ]
                ],
                'aggs' => [
                    'by_term' => [
                        'terms' => [
                            'field' => $field,
                            'size' => $size
                        ]
                    ]
                ]
            ]
        ];

        // Applica gli stessi filtri temporali se presenti
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $range = ['range' => ['valid_from' => []]];

            if (!empty($filters['date_from'])) {
                $range['range']['valid_from']['gte'] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $range['range']['valid_from']['lte'] = $filters['date_to'];
            }

            $query['body']['query']['bool']['must'][] = $range;
        }

        $response = $client->search($query);
        return $response['aggregations']['by_term']['buckets'];
    }

    /**
     * Get geo-based aggregations from Elasticsearch
     */
    protected function getGeoBasedMetrics(Model $model, string $geo_field = 'geocode', array $filters = []): array
    {
        $client = $model->getElasticsearchClient();

        $query = [
            'index' => $model->searchableAs(),
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            ['match' => ['entity' => $model->getTable()]]
                        ]
                    ]
                ],
                'aggs' => [
                    'geo_clusters' => [
                        'geohash_grid' => [
                            'field' => $geo_field,
                            'precision' => 5
                        ]
                    ]
                ]
            ]
        ];

        $response = $client->search($query);
        return $response['aggregations']['geo_clusters']['buckets'];
    }
}
