<?php

declare(strict_types=1);

namespace Modules\CMS\ApplicationContent;

use Illuminate\Database\Eloquent\Builder;
use Modules\CMS\Models\Content;
use Modules\Core\ApplicationContent\Contracts\ApplicationContentRetrievalProviderInterface;
use Modules\Core\ApplicationContent\Data\ApplicationContentAuthorization;
use Modules\Core\ApplicationContent\Data\ApplicationContentQuery;
use Modules\Core\ApplicationContent\Data\ApplicationContentResult;
use Modules\Core\ApplicationContent\Data\ApplicationContentSourceDescriptor;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Search\DTOs\AdvancedSearchResult;
use Modules\Core\Search\Services\AdvancedSearchService;
use Modules\Core\Services\Authorization\AuthorizationService;
use Modules\Core\Services\Crud\QueryBuilder;
use Override;
use Throwable;

final readonly class CmsApplicationContentRetrievalProvider implements ApplicationContentRetrievalProviderInterface
{
    public function __construct(
        private AdvancedSearchService $search,
        private AuthorizationService $authorization,
        private QueryBuilder $queryBuilder,
        private CmsContentEvidenceProjector $projector,
    ) {}

    #[Override]
    public function descriptor(): ApplicationContentSourceDescriptor
    {
        $locales = array_values(array_unique(array_filter(
            array_merge(LocaleContext::getAvailable(), [(string) config('app.locale', 'en')]),
            static fn (mixed $locale): bool => is_string($locale)
                && preg_match('/^[a-z]{2,3}(?:[-_][A-Z]{2})?$/', $locale) === 1,
        )));
        sort($locales, SORT_STRING);

        return new ApplicationContentSourceDescriptor(
            source: 'cms.contents',
            module: 'cms',
            entity: 'contents',
            supportedLocales: array_slice($locales, 0, 20),
            capabilities: ['hybrid', 'lexical', 'locale', 'semantic'],
            intentCategories: ['application_content', 'cms', 'content'],
        );
    }

    #[Override]
    public function retrieve(
        ApplicationContentQuery $query,
        ApplicationContentAuthorization $authorization,
    ): ApplicationContentResult {
        $connection = (new Content)->getConnectionName() ?: 'default';
        $window = min(50, $query->limit + 1);
        $search_result = $this->advancedSearch($query, $authorization->filters);
        $ranked = $this->rankedHits($search_result, $window, $connection);

        if ($ranked === []) {
            $search_result = $this->lexicalFallback($query, $authorization);
            $ranked = $this->rankedHits($search_result, $window, $connection);
        }

        $strategy = $this->strategy($search_result);
        $records = $this->rehydrate(array_keys($ranked), $authorization);
        $hits = [];

        foreach ($ranked as $record_id => $score) {
            $content = $records[$record_id] ?? null;

            if (! $content instanceof Content) {
                continue;
            }

            $hit = $this->projector->project($content, $query->locale, $strategy, $score);

            if ($hit !== null) {
                $hits[] = $hit;
            }
        }

        $truncated = $query->limit < count($hits);

        return new ApplicationContentResult(
            source: 'cms.contents',
            hits: array_slice($hits, 0, $query->limit),
            strategy: $strategy,
            truncated: $truncated,
        );
    }

    private function advancedSearch(
        ApplicationContentQuery $query,
        ?FiltersGroup $filters,
    ): AdvancedSearchResult {
        try {
            return $this->search->search(
                model: new Content,
                query: $query->query,
                page: 1,
                perPage: min(50, $query->limit + 1),
                filters: $filters,
            );
        } catch (Throwable) {
            return AdvancedSearchResult::empty(1, min(50, $query->limit + 1), ['degraded' => ['lexical_fallback']]);
        }
    }

    private function lexicalFallback(
        ApplicationContentQuery $query,
        ApplicationContentAuthorization $authorization,
    ): AdvancedSearchResult {
        $database_query = $this->authorizedQuery($authorization);
        $default_locale = (string) config('app.locale', 'en');
        $locales = array_values(array_unique([$query->locale, $default_locale]));
        $needle = '%' . addcslashes($query->query, '\\%_') . '%';

        $database_query->whereHas('translations', static function (Builder $translations) use ($locales, $needle): void {
            $translations
                ->whereIn('locale', $locales)
                ->where('title', 'like', $needle);
        });

        $ids = $database_query
            ->orderBy((new Content)->qualifyColumn('id'))
            ->limit(min(50, $query->limit + 1))
            ->pluck((new Content)->qualifyColumn('id'))
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();

        $hits = [];

        foreach ($ids as $position => $id) {
            $hits[] = [
                'id' => $id,
                'score' => round(1 / ($position + 1), 6),
                'source' => ['connection' => (new Content)->getConnectionName() ?: 'default'],
            ];
        }

        return new AdvancedSearchResult(
            hits: $hits,
            total: count($hits),
            page: 1,
            perPage: min(50, $query->limit + 1),
            totalPages: $hits === [] ? 0 : 1,
            meta: ['strategies' => ['keyword'], 'degraded' => ['lexical_fallback']],
        );
    }

    /**
     * @param  list<string>  $recordIds
     * @return array<string, Content>
     */
    private function rehydrate(
        array $recordIds,
        ApplicationContentAuthorization $authorization,
    ): array {
        if ($recordIds === []) {
            return [];
        }

        return $this->authorizedQuery($authorization)
            ->whereKey($recordIds)
            ->with('translations')
            ->get()
            ->mapWithKeys(static fn (Content $content): array => [(string) $content->getKey() => $content])
            ->all();
    }

    /**
     * @return Builder<Content>
     */
    private function authorizedQuery(ApplicationContentAuthorization $authorization): Builder
    {
        $query = Content::query()->valid();

        $this->authorization->applyAclFiltersToQuery($query, $authorization->permissionName);

        if ($authorization->filters instanceof FiltersGroup) {
            $this->queryBuilder->applyFilters($query, $authorization->filters);
        }

        return $query;
    }

    /**
     * @return array<string, float>
     */
    private function rankedHits(AdvancedSearchResult $result, int $limit, string $connection): array
    {
        $ranked = [];

        foreach ($result->hits as $hit) {
            $id = $hit['id'] ?? null;
            $score = $hit['score'] ?? null;
            $source = is_array($hit['source'] ?? null) ? $hit['source'] : [];

            if (! is_string($id)
                || $id === ''
                || ($source['connection'] ?? null) !== $connection
                || isset($ranked[$id])) {
                continue;
            }

            $ranked[$id] = is_numeric($score)
                ? max(0.0, min(1.0, (float) $score))
                : 0.0;

            if ($limit <= count($ranked)) {
                break;
            }
        }

        return $ranked;
    }

    private function strategy(AdvancedSearchResult $result): string
    {
        $strategies = is_array($result->meta['strategies'] ?? null)
            ? $result->meta['strategies']
            : [];

        if (in_array('hybrid', $strategies, true)
            || (in_array('keyword', $strategies, true) && in_array('vector', $strategies, true))) {
            return 'hybrid';
        }

        return in_array('vector', $strategies, true) ? 'semantic' : 'lexical';
    }
}
