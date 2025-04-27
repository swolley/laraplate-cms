<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Modules\Cms\Models\Content;
use Modules\Core\Cache\CacheManager;


class ContentAnalytics extends AbstractAnalytics
{
    public function __construct(private readonly Content $model) {}

    private static array $cache_duration = [555, 600];

    /**
     * Get content publication trends
     */
    public function getPublicationTrends(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('publication_trends', $filters),
            fn() => $this->getTimeBasedMetrics($this->model, 'created_at', 'day', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get author performance metrics
     */
    public function getAuthorMetrics(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('author_metrics', $filters),
            fn() => $this->getTermBasedMetrics($this->model, 'authors_id', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get category distribution
     */
    public function getCategoryDistribution(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('category_distribution', $filters),
            fn() => $this->getTermBasedMetrics($this->model, 'categories_id', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get tag usage metrics
     */
    public function getTagMetrics(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('tag_metrics', $filters),
            fn() => $this->getTermBasedMetrics($this->model, 'tags_id', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get geographic distribution of content
     */
    public function getGeographicDistribution(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('geographic_distribution', $filters),
            fn() => $this->getGeoBasedMetrics($this->model, 'location.geocode', $filters),
            self::$cache_duration
        );
    }

    /**
     * Get content quality metrics based on multiple factors
     */
    public function getQualityMetrics(array $filters = []): array
    {
        return CacheManager::remember(
            $this->getCacheKey('quality_metrics', $filters),
            function () use ($filters) {
                $client = $this->model->getElasticsearchClient();

                // Qui possiamo implementare metriche di qualità più complesse
                // Per esempio:
                // - Lunghezza del contenuto
                // - Presenza di media
                // - Completezza dei metadati
                // - Score basati su embedding

                return [];
            },
            self::$cache_duration
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheKey(string $metric, array $filters = []): string
    {
        return sprintf(
            'content_analytics:%s:%s',
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
