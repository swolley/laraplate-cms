<?php

declare(strict_types=1);

use Modules\Cms\Actions\Locations\GeocodeLocationAction;
use Modules\Cms\Services\Contracts\IGeocodingService;
use Tests\TestCase;

final class GeocodeLocationActionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_invokes_geocoding_service(): void
    {
        $service = \Mockery::mock(IGeocodingService::class);
        $service->shouldReceive('search')
            ->once()
            ->with('query', 'city', 'province', 'country')
            ->andReturn(['result']);

        $action = new GeocodeLocationAction($service);

        $this->assertSame(
            ['result'],
            $action('query', 'city', 'province', 'country'),
        );
    }
}

