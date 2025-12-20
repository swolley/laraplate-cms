<?php

declare(strict_types=1);

use Modules\Cms\Services\NominatimService;

it('has proper class structure', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has getInstance method as static', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);
    $method = $reflection->getMethod('getInstance');

    expect($method->isStatic())->toBeTrue();
    expect($method->isPublic())->toBeTrue();
});

it('has private constructor for singleton', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);
    $constructor = $reflection->getConstructor();

    expect($constructor->isPrivate())->toBeTrue();
});

it('has search method with correct signature', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);
    $method = $reflection->getMethod('search');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
});

it('has url method with correct signature', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);
    $method = $reflection->getMethod('url');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);
});

it('has proper method visibility', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    $getInstanceMethod = $reflection->getMethod('getInstance');

    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
    expect($getInstanceMethod->isPublic())->toBeTrue();
});

it('has consistent method signatures', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class hierarchy', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->getName())->toBe(NominatimService::class);
});

it('has required public methods', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $methodNames = array_map(static fn ($method) => $method->getName(), $publicMethods);

    expect($methodNames)->toContain('search');
    expect($methodNames)->toContain('url');
    expect($methodNames)->toContain('getInstance');
});

it('has proper class finality', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
});

it('has proper namespace', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->getName())->toBe('Modules\Cms\Services\NominatimService');
});

it('has proper method accessibility', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    $getInstanceMethod = $reflection->getMethod('getInstance');

    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
    expect($getInstanceMethod->isPublic())->toBeTrue();
});

it('has proper class structure for geocoding', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method parameter types', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class structure for singleton pattern', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();

    $constructor = $reflection->getConstructor();
    expect($constructor->isPrivate())->toBeTrue();
});

it('has proper method return types', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->getReturnType())->not->toBeNull();
    expect($urlMethod->getReturnType())->not->toBeNull();
});

it('has proper class structure for service pattern', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method signatures for geocoding', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class structure for API integration', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method structure for geocoding operations', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
});

it('has proper class structure for singleton service', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();

    $constructor = $reflection->getConstructor();
    expect($constructor->isPrivate())->toBeTrue();
});

it('has proper method structure for API calls', static function (): void {
    $reflection = new ReflectionClass(NominatimService::class);

    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');

    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});
