<?php

declare(strict_types=1);

namespace Modules\CMS\Analytics;

use Illuminate\Support\Facades\Cache;
use JsonException;
use Modules\CMS\Models\Content;

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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
     *
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
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
            'content_analytics:%s:%s',
            $metric,
            md5($encoded_filters),
        );
    }

    private function cacheTtlSeconds(): int
    {
        return random_int(self::$cache_ttl_range_seconds[0], self::$cache_ttl_range_seconds[1]);
    }
}
