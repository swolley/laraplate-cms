<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\ContentRating;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Models\Translations\ContributorTranslation;
use Modules\CMS\Models\Translations\TagTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\SettingTypeEnum;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Models\Setting;
use Modules\Core\Models\Translations\TaxonomyTranslation;
use Modules\Core\Models\Version;
use Modules\Core\Seeding\ModelCapabilityScanner;
use Modules\Core\Services\ForcedVersionStrategySettings;
use Modules\Core\Services\PerModelSettingResolver;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Comments and ratings are contributions of somebody else, not part of the document (D8); a translation's state
 * belongs to its owner's history (D10). None of them is versioned, whatever the per-table setting says, and the
 * seeder creates no setting for them.
 */
dataset('never versioned models', [
    'comment' => [Comment::class],
    'content rating' => [ContentRating::class],
    'content translation' => [ContentTranslation::class],
    'comment translation' => [CommentTranslation::class],
    'contributor translation' => [ContributorTranslation::class],
    'tag translation' => [TagTranslation::class],
    'taxonomy translation' => [TaxonomyTranslation::class],
]);

it('never versions the model, even when its setting asks for it', function (string $model_class): void {
    $model = new $model_class;
    Setting::factory()->persistedWithoutApprovalCapture()->create([
        'name' => PerModelSettingResolver::nameFor(CoreDatabaseSeeder::VERSIONING_NAME_PREFIX, $model->getTable()),
        'value' => 'DIFF',
        'type' => SettingTypeEnum::Json,
        'group_name' => 'versioning',
        'description' => 'A stale setting',
    ]);

    expect($model->getVersionStrategy())->toBeFalse();
})->with('never versioned models');

it('gives the model no versioning setting and hides a stale one', function (string $model_class): void {
    $capability = collect(app(ModelCapabilityScanner::class)->scan())->firstWhere('modelClass', $model_class);
    $name = PerModelSettingResolver::nameFor(CoreDatabaseSeeder::VERSIONING_NAME_PREFIX, (new $model_class)->getTable());

    expect($capability?->hasVersions)->toBeFalse()
        ->and(app(ForcedVersionStrategySettings::class)->isForced($name))->toBeTrue();
})->with('never versioned models');

it('writes no version row for a new comment', function (): void {
    setupCMSEntities();
    $before = Version::query()->where('versionable_type', (new Comment)->getMorphClass())->count();

    Comment::factory()->create();

    expect(Version::query()->where('versionable_type', (new Comment)->getMorphClass())->count())->toBe($before);
});
