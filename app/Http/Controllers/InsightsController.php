<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\CMS\Http\Requests\TagGraphRequest;
use Modules\CMS\Services\Graph\TagCoOccurrenceService;
use Modules\Core\Helpers\ResponseBuilder;

/**
 * Read-only endpoint backing the CMS tag-graph surfaces (the tag graph dashboard
 * widget and the tag facet graph selector). The map surface has no bespoke endpoint:
 * it is served by the generic CRUD select (a `contents` count aggregate on the
 * locations list plus place-derived coordinates and a bbox filter).
 */
final class InsightsController extends Controller
{
    public function __construct(
        private readonly TagCoOccurrenceService $tagGraph,
    ) {}

    public function tagGraph(TagGraphRequest $request): JsonResponse
    {
        $response = new ResponseBuilder($request);
        $response->setData($this->tagGraph->graph($request->minCoOccurrence(), $request->maxNodes()));

        return $response->json();
    }
}
