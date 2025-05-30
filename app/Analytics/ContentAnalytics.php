<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Illuminate\Support\Facades\Cache;
use Modules\Cms\Models\Content;

final class ContentAnalytics extends AbstractAnalytics
{
    /**
     * @var int[]
     */
    private static array $cache_duration = [555, 600];

    public function __construct(private readonly Content $model) {}

    /**
     * Get content publication trends.
     */
    public function getPublicationTrends(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('publication_trends', $filters),
            self::$cache_duration,
            fn (): array => $this->getTimeBasedMetrics($this->model, 'created_at', 'day', $filters),
        );
    }

    /**
     * Get author performance metrics.
     */
    public function getAuthorMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('author_metrics', $filters),
            self::$cache_duration,
            fn (): array => $this->getTermBasedMetrics($this->model, 'authors_id', $filters),
        );
    }

    /**
     * Get category distribution.
     */
    public function getCategoryDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('category_distribution', $filters),
            self::$cache_duration,
            fn (): array => $this->getTermBasedMetrics($this->model, 'categories_id', $filters),
        );
    }

    /**
     * Get tag usage metrics.
     */
    public function getTagMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('tag_metrics', $filters),
            self::$cache_duration,
            fn (): array => $this->getTermBasedMetrics($this->model, 'tags_id', $filters),
        );
    }

    /**
     * Get geographic distribution of content.
     */
    public function getGeographicDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('geographic_distribution', $filters),
            self::$cache_duration,
            fn (): array => $this->getGeoBasedMetrics($this->model, 'location.geocode', $filters),
        );
    }

    /**
     * Get content quality metrics based on multiple factors.
     */
    public function getQualityMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('quality_metrics', $filters),
            self::$cache_duration,
            fn (): array
                // $client = $this->model->getElasticsearchClient();
                // Qui possiamo implementare metriche di qualità più complesse
                // Per esempio:
                // - Lunghezza del contenuto
                // - Presenza di media
                // - Completezza dei metadati
                // - Score basati su embedding
                => [],
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
            'content_analytics:%s:%s',
            $metric,
            md5(json_encode($filters)),
        );
    }
}
