<?php

declare(strict_types=1);

namespace Modules\Cms\Tests;

use Override;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Configurazioni specifiche per il modulo Cms
        $this->withoutExceptionHandling();
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            \Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
            \Spatie\Tags\TagsServiceProvider::class,
            \Modules\Cms\Providers\CmsServiceProvider::class,
        ];
    }
}
