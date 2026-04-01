<?php

declare(strict_types=1);

namespace Modules\Cms\Analytics;

use Illuminate\Support\Facades\Cache;
use Modules\Cms\Models\Content;

final class ContentAnalytics extends AbstractAnalytics
{
    /**
     * Inclusive min/max TTL in seconds (jitter to reduce cache stampedes).
     *
     * @var array{0: positive-int, 1: positive-int}
     */
    private static array $cache_ttl_range_seconds = [555, 600];

    public function __construct(private readonly Content $model) {}

    /**
     * Get content publication trends.
     */
    public function getPublicationTrends(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('publication_trends', $filters),
            $this->cacheTtlSeconds(),
            fn (): array => $this->getTimeBasedMetrics($this->model, 'created_at', 'day', $filters),
        );
    }

    /**
     * Get contributor performance metrics.
     */
    public function getContributorMetrics(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('contributor_metrics', $filters),
            $this->cacheTtlSeconds(),
            fn (): array => $this->getTermBasedMetrics($this->model, 'contributors_id', $filters),
        );
    }

    /**
     * Get category distribution.
     */
    public function getCategoryDistribution(array $filters = []): array
    {
        return Cache::remember(
            $this->getCacheKey('category_distribution', $filters),
            $this->cacheTtlSeconds(),
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
            $this->cacheTtlSeconds(),
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
            $this->cacheTtlSeconds(),
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
            $this->cacheTtlSeconds(),
            // $client = $this->model->getElasticsearchClient();
            // Qui possiamo implementare metriche di qualità più complesse
            // Per esempio:
            // - Lunghezza del contenuto
            // - Presenza di media
            // - Completezza dei metadati
            // - Score basati su embedding
            fn (): array => [],
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

    private function cacheTtlSeconds(): int
    {
        return random_int(self::$cache_ttl_range_seconds[0], self::$cache_ttl_range_seconds[1]);
    }
}
