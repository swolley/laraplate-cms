<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\SettingTypeEnum;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Enums\VersionChangeType;
use Modules\Core\Models\Setting;
use Modules\Core\Models\Version;
use Modules\Core\Services\PerModelSettingResolver;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The translations of a content or of a category are rows of their owner's history, one per locale (D10, D28).
 */
beforeEach(function (): void {
    setupCMSEntities();

    // Versioning is a per-table setting the tests do not seed: switch it on for the two owners.
    foreach ([new Content, new Category] as $owner) {
        Setting::factory()->persistedWithoutApprovalCapture()->create([
            'name' => PerModelSettingResolver::nameFor(CoreDatabaseSeeder::VERSIONING_NAME_PREFIX, $owner->getTable()),
            'value' => 'DIFF',
            'type' => SettingTypeEnum::Json,
            'group_name' => 'versioning',
            'description' => 'Versioning switched on for the test',
        ]);
    }

    app(PerModelSettingResolver::class)->flush();
    Content::resetVersionStrategyCache();
});

/**
 * @return list<string>
 */
function translation_rows_of(Illuminate\Database\Eloquent\Model $owner): array
{
    return Version::query()
        ->where('versionable_type', $owner->getMorphClass())
        ->where('versionable_id', $owner->getKey())
        ->where('relation_path', 'translations')
        ->orderBy('id')
        ->get()
        ->map(static fn (Version $row): string => $row->change_type->value . ':' . $row->subject_key['locale'])
        ->all();
}

it('records every locale of a new content in the content history', function (): void {
    $content = Content::factory()->create();

    $locales = $content->translations()->pluck('locale')->sort()->values()->all();
    $rows = collect(translation_rows_of($content))->sort()->values()->all();

    expect($rows)->toBe(array_map(static fn (string $locale): string => 'created:' . $locale, $locales));
});

it('records a change of a content translation in the content history', function (): void {
    $content = Content::factory()->create();
    $locale = (string) $content->translations()->orderBy('locale')->value('locale');
    $before = translation_rows_of($content);

    $content->setTranslation($locale, ['title' => 'New title', 'slug' => 'new-title', 'components' => []]);

    expect(array_slice(translation_rows_of($content), count($before)))->toBe([VersionChangeType::Updated->value . ':' . $locale]);
});

it('records a change of a category translation in the category history, not in the abstract taxonomy', function (): void {
    $category = Category::factory()->create();
    $locale = (string) $category->translations()->orderBy('locale')->value('locale');
    $before = translation_rows_of($category);

    $category->setTranslation($locale, ['name' => 'New category', 'slug' => 'new-category', 'components' => []]);

    expect(array_slice(translation_rows_of($category), count($before)))->toBe([VersionChangeType::Updated->value . ':' . $locale]);
});
