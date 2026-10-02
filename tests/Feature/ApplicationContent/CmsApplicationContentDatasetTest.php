<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Tests\Stubs\ApplicationContent\EvaluationContentCorpus;
use Modules\CMS\Tests\TestCase;
use Modules\Core\ApplicationContent\Contracts\ApplicationContentRetrievalProviderRegistryInterface;
use Modules\Core\ApplicationContent\Data\ApplicationContentAuthorization;
use Modules\Core\ApplicationContent\Data\ApplicationContentQuery;
use Modules\Core\ApplicationContent\Data\ApplicationContentResult;
use Modules\Core\Casts\Filter;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Casts\WhereClause;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The cases of CMS's evaluation dataset. These tests cover CMS's side of it: the
 * `cms.contents` provider answers the cases over the corpus they were written for, within
 * each case's authorization. Scoring the answers (MRR, nDCG, the committed baseline report)
 * belongs to whoever evaluates retrieval, not to CMS.
 *
 * @return list<array<string, mixed>>
 */
function cmsDatasetCases(): array
{
    $dataset = json_decode((string) file_get_contents(module_path('CMS', EvaluationContentCorpus::DATASET)), true, flags: JSON_THROW_ON_ERROR);

    return $dataset['cases'];
}

/**
 * @param  array<string, mixed>  $group
 */
function cmsDatasetFilters(array $group): FiltersGroup
{
    return new FiltersGroup(
        array_map(
            static fn (array $filter): Filter|FiltersGroup => array_key_exists('filters', $filter)
                ? cmsDatasetFilters($filter)
                : new Filter($filter['property'], $filter['value'], FilterOperator::from($filter['operator'])),
            $group['filters'],
        ),
        WhereClause::from($group['operator']),
    );
}

/**
 * @param  array<string, mixed>  $case
 */
function retrieveCmsDatasetCase(array $case): ApplicationContentResult
{
    $provider = app(ApplicationContentRetrievalProviderRegistryInterface::class)->providerFor('cms.contents');
    expect($provider)->not->toBeNull();

    $filters = $case['authorization']['filters'];

    return $provider->retrieve(
        new ApplicationContentQuery('cms.contents', $case['query'], $case['locale'], $case['limit']),
        new ApplicationContentAuthorization($case['authorization']['permission'], $filters === null ? null : cmsDatasetFilters($filters)),
    );
}

beforeEach(function (): void {
    EvaluationContentCorpus::create();
});

it('ranks the record an exact-title case expects first, citing its canonical reference', function (): void {
    $exact_cases = array_filter(
        cmsDatasetCases(),
        static fn (array $case): bool => in_array('exact', $case['slices'], true) && $case['expected_hit_ids'] !== [],
    );
    expect($exact_cases)->not->toBeEmpty();

    foreach ($exact_cases as $case) {
        $hits = retrieveCmsDatasetCase($case)->hits;

        expect($hits)->not->toBeEmpty()
            ->and($hits[0]->id)->toBe($case['expected_hit_ids'][0])
            ->and($hits[0]->canonicalReference)->toBe($case['expected_citation_references'][0]);
    }
});

it('returns nothing for scheduled, expired, deleted, unauthorized or untranslated records', function (): void {
    $empty_cases = array_filter(cmsDatasetCases(), static fn (array $case): bool => $case['expect_authorized_empty'] === true);
    expect($empty_cases)->not->toBeEmpty();

    foreach ($empty_cases as $case) {
        expect(retrieveCmsDatasetCase($case)->hits)->toBe([], "case {$case['id']}");
    }
});

it('cites every returned record by its content id, within the case limit', function (): void {
    foreach (cmsDatasetCases() as $case) {
        $hits = retrieveCmsDatasetCase($case)->hits;

        expect(count($hits))->toBeLessThanOrEqual($case['limit']);

        foreach ($hits as $hit) {
            expect($hit->id)->toMatch('/^cms\.contents:\d+$/')
                ->and($hit->canonicalReference)->toBe('/app/cms/contents/' . mb_substr($hit->id, mb_strlen('cms.contents:')));
        }
    }
});
