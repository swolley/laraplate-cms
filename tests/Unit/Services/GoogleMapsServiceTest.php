<?php

declare(strict_types=1);

use Modules\Cms\Services\GoogleMapsService;

it('has proper class structure', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has getInstance method as static', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    $method = $reflection->getMethod('getInstance');
    
    expect($method->isStatic())->toBeTrue();
    expect($method->isPublic())->toBeTrue();
});

it('has private constructor for singleton', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    $constructor = $reflection->getConstructor();
    
    expect($constructor->isPrivate())->toBeTrue();
});

it('has search method with correct signature', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    $method = $reflection->getMethod('search');
    
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
});

it('has url method with correct signature', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    $method = $reflection->getMethod('url');
    
    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(1);
});

it('has proper method visibility', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    $getInstanceMethod = $reflection->getMethod('getInstance');
    
    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
    expect($getInstanceMethod->isPublic())->toBeTrue();
});

it('has consistent method signatures', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class hierarchy', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->getName())->toBe(GoogleMapsService::class);
});

it('has required public methods', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $methodNames = array_map(fn($method) => $method->getName(), $publicMethods);
    
    expect($methodNames)->toContain('search');
    expect($methodNames)->toContain('url');
    expect($methodNames)->toContain('getInstance');
});

it('has proper class finality', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
});

it('has proper namespace', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->getName())->toBe('Modules\Cms\Services\GoogleMapsService');
});

it('has proper method accessibility', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    $getInstanceMethod = $reflection->getMethod('getInstance');
    
    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
    expect($getInstanceMethod->isPublic())->toBeTrue();
});

it('has proper class structure for geocoding', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method parameter types', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class structure for singleton pattern', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();
    
    $constructor = $reflection->getConstructor();
    expect($constructor->isPrivate())->toBeTrue();
});

it('has proper method return types', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->getReturnType())->not->toBeNull();
    expect($urlMethod->getReturnType())->not->toBeNull();
});

it('has proper class structure for service pattern', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method signatures for geocoding', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});

it('has proper class structure for API integration', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('search'))->toBeTrue();
    expect($reflection->hasMethod('url'))->toBeTrue();
});

it('has proper method structure for geocoding operations', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->isPublic())->toBeTrue();
    expect($urlMethod->isPublic())->toBeTrue();
});

it('has proper class structure for singleton service', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    expect($reflection->isFinal())->toBeTrue();
    expect($reflection->hasMethod('getInstance'))->toBeTrue();
    
    $constructor = $reflection->getConstructor();
    expect($constructor->isPrivate())->toBeTrue();
});

it('has proper method structure for API calls', function (): void {
    $reflection = new ReflectionClass(GoogleMapsService::class);
    
    $searchMethod = $reflection->getMethod('search');
    $urlMethod = $reflection->getMethod('url');
    
    expect($searchMethod->getNumberOfParameters())->toBeGreaterThanOrEqual(1);
    expect($urlMethod->getNumberOfParameters())->toBe(1);
});