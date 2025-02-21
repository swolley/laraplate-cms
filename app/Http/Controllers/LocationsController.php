<?php

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Helpers\ResponseBuilder;
use Modules\Cms\Http\Requests\GeocodeRequest;
use Modules\Cms\Services\Contracts\GeocodingServiceInterface;

class LocationsController extends Controller
{
    private readonly GeocodingServiceInterface $geocoding_service;

    public function __construct(GeocodingServiceInterface $geocoding_service)
    {
        $this->geocoding_service = $geocoding_service;
    }

    public function geocode(GeocodeRequest $request): JsonResponse
    {
        $response = new ResponseBuilder($request);
        try {
            $location = $this->geocoding_service->search(
                $request->get('q'),
                $request->get('city'),
                $request->get('province'),
                $request->get('country')
            );
            $response->setData($location);
        } catch (\Exception $e) {
            $response->setError($e->getMessage());
        }

        return $response->json();
    }
}
