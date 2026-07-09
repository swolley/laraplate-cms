<?php

declare(strict_types=1);

namespace Modules\CMS\Providers;

use Modules\CMS\Import\Support\CategoryHierarchySorter;
use Modules\CMS\Import\Support\ContributorDefaults;
use Modules\CMS\Import\Support\ContributorMatcher;
use Modules\CMS\Import\Support\DefaultContributorProvisioner;
use Modules\CMS\Import\Support\EntityPresetResolver;
use Modules\CMS\Import\Support\ExternalReferenceLocator;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Support\ImportReferenceResolver;
use Modules\CMS\Import\Support\ImportPostProcessor;
use Modules\CMS\Import\Support\ImportPresetProvisioner;
use Modules\CMS\Import\Support\RelatedContentResolver;
use Modules\CMS\Import\Upserters\CategoryUpserter;
use Modules\CMS\Import\Upserters\ContentUpserter;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Import\Upserters\LocationUpserter;
use Modules\CMS\Import\Upserters\TagUpserter;
use Override;

final class ImportServiceProvider extends \Illuminate\Support\ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $locale = static fn (): string => (string) config('cms.import.locale', config('app.locale'));

        $this->app->singleton(ImportIdMap::class);
        $this->app->singleton(ImportReferenceResolver::class);

        $this->app->when([
            ExternalReferenceLocator::class,
            CategoryUpserter::class,
            ContributorUpserter::class,
            ContributorMatcher::class,
            DefaultContributorProvisioner::class,
            TagUpserter::class,
            ContentUpserter::class,
        ])->needs('$locale')->give($locale);

        $this->app->singleton(ExternalReferenceLocator::class);
        $this->app->singleton(CategoryHierarchySorter::class);
        $this->app->singleton(EntityPresetResolver::class);
        $this->app->singleton(ContributorMatcher::class);
        $this->app->singleton(DefaultContributorProvisioner::class);
        $this->app->singleton(ImportPresetProvisioner::class);
        $this->app->singleton(ImportPostProcessor::class);
        $this->app->singleton(RelatedContentResolver::class);
        $this->app->singleton(CategoryUpserter::class);
        $this->app->singleton(ContributorUpserter::class);
        $this->app->singleton(TagUpserter::class);
        $this->app->singleton(LocationUpserter::class);
        $this->app->singleton(ContentUpserter::class);
        $this->app->singleton(ContributorDefaults::class);
    }
}
