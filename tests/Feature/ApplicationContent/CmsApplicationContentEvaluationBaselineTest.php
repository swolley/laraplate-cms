<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AI\Services\ApplicationContent\Evaluation\ApplicationContentEvaluationDataset;
use Modules\AI\Services\ApplicationContent\Evaluation\ApplicationContentEvaluationService;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\ApplicationContent\Contracts\ApplicationContentRetrievalProviderRegistryInterface;

uses(TestCase::class, RefreshDatabase::class);

function createCmsEvaluationContent(
    int $id,
    string $title,
    string $locale = 'en',
    array $attributes = [],
    ?string $body = null,
): Content {
    $content = Content::factory()->create(array_merge([
        'id' => $id,
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ], $attributes));
    $content->setTranslation($locale, [
        'title' => $title,
        'slug' => Str::slug($title) . '-' . $id,
        'components' => ['content' => $body ?? "Generated evidence for {$title}."],
    ])->save();

    return $content->fresh();
}

it('reproduces the committed record-level baseline from generated CMS records', function (): void {
    setupCMSEntities([EntityType::Contents]);
    $exact = [
        9101 => ['account setup guide', 'en'],
        9102 => ['notification settings guide', 'en'],
        9105 => ['language preferences', 'en'],
        9106 => ['preferenze lingua', 'it'],
        9107 => ['fallback language guide', 'en'],
        9109 => ['visible workspace guide', 'en'],
        9111 => ['current workspace onboarding', 'en'],
        9118 => ['long guide introduction', 'en'],
        9120 => ['canonical reference guide', 'en'],
        9121 => ['configurazione accessibilità', 'it'],
        9122 => ['guide: filters, sorting and search', 'en'],
        9123 => ['search search filters filters', 'en'],
        9125 => ['lexical fallback marker', 'en'],
        9126 => ['primary matching guide', 'en'],
        9127 => ['secondary matching guide', 'en'],
        9128 => ['bounded result guide', 'en'],
        9130 => ['content navigation guide', 'en'],
    ];

    foreach ($exact as $id => [$title, $locale]) {
        createCmsEvaluationContent($id, $title, $locale);
    }

    createCmsEvaluationContent(9103, 'Profile appearance options');
    createCmsEvaluationContent(9104, 'Alert delivery preferences');
    createCmsEvaluationContent(9119, 'Extended reference manual', body: 'Specific detail inside a long guide.');
    createCmsEvaluationContent(9124, 'Draft recovery', body: 'Recover work after an interrupted session.');
    createCmsEvaluationContent(9113, 'future availability guide', attributes: ['valid_from' => now()->addDay()]);
    createCmsEvaluationContent(9114, 'expired availability guide', attributes: [
        'valid_from' => now()->subDays(2),
        'valid_to' => now()->subDay(),
    ]);
    createCmsEvaluationContent(9115, 'deleted record guide')->delete();

    $dataset = ApplicationContentEvaluationDataset::fromFile(
        module_path('CMS', 'tests/Fixtures/application-content/cms-contents.json'),
    );
    $provider = app(ApplicationContentRetrievalProviderRegistryInterface::class)->providerFor('cms.contents');
    expect($provider)->not->toBeNull();
    $tick = 0.0;
    $evaluation = new ApplicationContentEvaluationService(
        clock: static function () use (&$tick): float {
            $current = $tick;
            $tick += 0.01;

            return $current;
        },
    );
    $report = $evaluation->evaluate(
        $dataset,
        'cms.contents',
        'database-generated-fixture',
        static fn ($query, $authorization) => $provider->retrieve($query, $authorization),
    );
    $artifact_path = module_path('CMS', 'docs/evaluations/application-content/2026-07-record-baseline.json');
    $artifact = json_decode((string) file_get_contents($artifact_path), true, flags: JSON_THROW_ON_ERROR);

    expect($report)->toBe($artifact)
        ->and($report['metrics']['unavailable_rate'])->toBe(0.0)
        ->and($report['slices']['category']['passage_candidate']['supported_answer_rate'])->toBe(0.0);
});
