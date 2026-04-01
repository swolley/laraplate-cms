<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\AbstractGeocodingService;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class);

it('caches resolver result when remember succeeds', function (): void {
    config(['cache.default' => 'array']);
    Cache::flush();

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            return null;
        }

        protected function getAddressDetails(array $result): Location
        {
            throw new BadMethodCallException();
        }

        protected function getSearchUrl(string $search_string): string
        {
            return '';
        }
    };

    $method = new ReflectionMethod($service, 'rememberGeocodingThroughCache');
    $method->setAccessible(true);

    $calls = 0;
    $resolver = function () use (&$calls): array {
        $calls++;

        return ['ok' => true];
    };

    $first = $method->invoke($service, 'geo:test-key', 120, $resolver);
    $second = $method->invoke($service, 'geo:test-key', 120, $resolver);

    expect($first)->toBe(['ok' => true])
        ->and($second)->toBe(['ok' => true])
        ->and($calls)->toBe(1);
});

it('falls back to resolver when remember throws', function (): void {
    Cache::partialMock();
    Cache::shouldReceive('remember')
        ->once()
        ->andThrow(new RuntimeException('cache failure'));

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            return null;
        }

        protected function getAddressDetails(array $result): Location
        {
            throw new BadMethodCallException();
        }

        protected function getSearchUrl(string $search_string): string
        {
            return '';
        }
    };

    $method = new ReflectionMethod($service, 'rememberGeocodingThroughCache');
    $method->setAccessible(true);

    $calls = 0;
    $result = $method->invoke($service, 'k', 60, function () use (&$calls): string {
        $calls++;

        return 'fresh';
    });

    expect($calls)->toBe(1)
        ->and($result)->toBe('fresh');
});
