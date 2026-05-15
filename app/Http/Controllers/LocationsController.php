<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CMS\Actions\Locations\GeocodeLocationAction;
use Modules\CMS\Http\Requests\GeocodeRequest;
use Modules\Core\Helpers\ResponseBuilder;

final class LocationsController extends Controller
{
    public function __construct(private readonly GeocodeLocationAction $geocodeLocationAction) {}

    public function geocode(GeocodeRequest $request): JsonResponse
    {
        $response = new ResponseBuilder($request);

        try {
            $location = ($this->geocodeLocationAction)(
                $request->get('q'),
                $request->get('city'),
                $request->get('province'),
                $request->get('country'),
            );
            $response->setData($location);
        } catch (Exception $exception) {
            $response->setError($exception->getMessage());
        }

        return $response->json();
    }
}
