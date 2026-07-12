<?php

declare(strict_types=1);

use Modules\CMS\Graph\CmsGraphProvider;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Graph\Contracts\GraphProviderRegistryInterface;

uses(TestCase::class);

it('registers cms graph defaults through the provider registry', function (): void {
    $provider = app(GraphProviderRegistryInterface::class)->providerFor('cms', 'contents');

    expect($provider)->toBeInstanceOf(CmsGraphProvider::class);
    expect($provider?->defaultRelations('cms', 'contents'))->toContain('tags');
    expect($provider?->summaryFields('cms', 'contents'))->toContain('title');
    expect($provider?->edgeType('cms', 'contents', 'tags'))->toBe('tagged_as');
});
