<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CMS\Http\Requests\MapLocationsRequest;
use Modules\CMS\Http\Requests\TagGraphRequest;
use Modules\CMS\Services\Graph\TagCoOccurrenceService;
use Modules\CMS\Services\Map\MapLocationsService;
use Modules\Core\Helpers\ResponseBuilder;

/**
 * Read-only endpoints that back the CMS map and tag-graph surfaces (dashboard
 * widgets and facet selectors): geo-located locations used in contents, and the
 * tag co-occurrence adjacency graph.
 */
final class InsightsController extends Controller
{
    public function __construct(
        private readonly MapLocationsService $mapLocations,
        private readonly TagCoOccurrenceService $tagGraph,
    ) {}

    public function mapLocations(MapLocationsRequest $request): JsonResponse
    {
        $response = new ResponseBuilder($request);
        $response->setData($this->mapLocations->usedLocations($request->bounds())->all());

        return $response->json();
    }

    public function tagGraph(TagGraphRequest $request): JsonResponse
    {
        $response = new ResponseBuilder($request);
        $response->setData($this->tagGraph->graph($request->minCoOccurrence(), $request->maxNodes()));

        return $response->json();
    }
}
