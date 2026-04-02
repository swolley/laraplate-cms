<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Modules\Cms\Models\Location;
use Modules\Cms\Services\Contracts\AbstractGeocodingService;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    /** @var TestCase $this */
    $this->geocoding_previous_env = $this->app['env'];
    $this->app['env'] = 'local';
    config(['cache.default' => 'array', 'cache.duration.long' => 120]);
    Cache::flush();
});

afterEach(function (): void {
    /** @var TestCase $this */
    $this->app['env'] = $this->geocoding_previous_env ?? 'testing';
});

it('getInstance returns the same singleton for a concrete implementation', function (): void {
    $service_class = new class extends AbstractGeocodingService
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
            return 'https://geo.test/' . $search_string;
        }
    };

    $first = $service_class::getInstance();
    $second = $service_class::getInstance();

    expect($first)->toBeInstanceOf(AbstractGeocodingService::class)
        ->and($second)->toBe($first);
});

it('url builds search string from coordinates and passes it to getSearchUrl', function (): void {
    $service = new class extends AbstractGeocodingService
    {
        public string $captured_search_string = '';

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
            $this->captured_search_string = $search_string;

            return 'https://nominatim.test/search?q=' . rawurlencode($search_string);
        }
    };

    $location = new Location;
    $location->geolocation = new Point(45.4642, 9.19);

    $url = $service->url($location);

    expect($service->captured_search_string)->toBe('45.4642,9.19')
        ->and($url)->toBe('https://nominatim.test/search?q=' . rawurlencode('45.4642,9.19'));
});

it('url builds search string from address fields when coordinates are absent', function (): void {
    $service = new class extends AbstractGeocodingService
    {
        public string $captured_search_string = '';

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
            $this->captured_search_string = $search_string;

            return 'https://nominatim.test/';
        }
    };

    $location = new Location;
    $location->address = 'Via Roma 1';
    $location->postcode = '20121';
    $location->city = 'Milan';
    $location->province = 'MI';
    $location->country = 'Italy';

    $service->url($location);

    expect($service->captured_search_string)->toBe('Via Roma 1 20121Milan, MI, Italy');
});

it('search bypasses rate limiter in testing environment', function (): void {
    $this->app['env'] = 'testing';

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            return ['testing-path' => $query];
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

    RateLimiter::shouldReceive('attempt')->never();

    $result = $service->search('rome');

    expect($result)->toBe(['testing-path' => 'rome']);
});

it('search uses rate limiter and returns callback result when not in testing', function (): void {
    RateLimiter::shouldReceive('attempt')
        ->once()
        ->withArgs(function (string $key, int $max_attempts, Closure $callback, int $decay): bool {
            return str_starts_with($key, 'nominatim:')
                && $max_attempts === 60
                && $decay === 1;
        })
        ->andReturnUsing(function (string $key, int $max_attempts, Closure $callback, int $decay): array {
            return $callback();
        });

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            return ['ok' => $query, 'limit' => $limit];
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

    expect($service->search('milan', null, null, null, 3))->toBe(['ok' => 'milan', 'limit' => 3]);
});

it('search returns null when rate limiter reports too many attempts', function (): void {
    RateLimiter::shouldReceive('attempt')
        ->once()
        ->andReturn(false);

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            throw new RuntimeException('performSearch must not run when rate limited');
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

    expect($service->search('blocked'))->toBeNull();
});

it('search returns null when rate limiter yields true after null geocoding result', function (): void {
    RateLimiter::shouldReceive('attempt')
        ->once()
        ->andReturn(true);

    $service = new class extends AbstractGeocodingService
    {
        protected function performSearch(string $query, ?string $city, ?string $province, ?string $country, int $limit): array|Location|null
        {
            throw new RuntimeException('callback should not run when attempt returns true without invoking closure');
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

    expect($service->search('noop'))->toBeNull();
});

it('search returns null when geocoding resolves to null under rate limiter', function (): void {
    RateLimiter::shouldReceive('attempt')
        ->once()
        ->andReturnUsing(function (string $key, int $max_attempts, Closure $callback, int $decay): bool {
            $callback();

            return true;
        });

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

    expect($service->search('empty'))->toBeNull();
});
