<?php

declare(strict_types=1);

namespace Modules\Cms\Actions\Locations;

use Modules\Cms\Services\Contracts\IGeocodingService;

final class GeocodeLocationAction
{
    public function __construct(
        private readonly IGeocodingService $geocodingService,
    ) {}

    public function __invoke(?string $query, ?string $city, ?string $province, ?string $country): array
    {
        return $this->geocodingService->search($query, $city, $province, $country);
    }
}
