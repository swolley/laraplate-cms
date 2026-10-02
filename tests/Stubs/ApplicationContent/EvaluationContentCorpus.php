<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Stubs\ApplicationContent;

use Illuminate\Support\Str;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;

/**
 * The generated content corpus behind the `cms.contents` evaluation dataset in
 * `tests/Fixtures/application-content/cms-contents.json`: fixed ids and titles, plus a
 * scheduled, an expired and a deleted record the dataset expects never to see.
 *
 * Shared by CMS's own dataset test and by the application's cross-module evaluation
 * baseline, so both always measure the same corpus. Needs `setupCMSEntities()` from
 * CMS's `tests/Pest.php`, which the application test bootstrap also loads.
 */
final class EvaluationContentCorpus
{
    public const string DATASET = 'tests/Fixtures/application-content/cms-contents.json';

    /**
     * Records whose title is matched exactly by a dataset case: id => [title, locale].
     */
    private const array EXACT = [
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

    public static function create(): void
    {
        setupCMSEntities([EntityType::Contents]);

        foreach (self::EXACT as $id => [$title, $locale]) {
            self::content($id, $title, $locale);
        }

        self::content(9103, 'Profile appearance options');
        self::content(9104, 'Alert delivery preferences');
        self::content(9119, 'Extended reference manual', body: 'Specific detail inside a long guide.');
        self::content(9124, 'Draft recovery', body: 'Recover work after an interrupted session.');
        self::content(9113, 'future availability guide', attributes: ['valid_from' => now()->addDay()]);
        self::content(9114, 'expired availability guide', attributes: [
            'valid_from' => now()->subDays(2),
            'valid_to' => now()->subDay(),
        ]);
        self::content(9115, 'deleted record guide')->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function content(
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
}
