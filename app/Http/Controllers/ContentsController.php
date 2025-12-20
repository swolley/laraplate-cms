<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Actions\Contents\GetContentsByRelationAction;
use Modules\Core\Http\Controllers\CrudController;
use Modules\Core\Http\Requests\ListRequest;

final class ContentsController extends CrudController
{
    public function __construct(
        private readonly GetContentsByRelationAction $getContentsByRelationAction,
    ) {}

    /**
     * @route-comment
     * Route(path: 'api/v1/{relation}/{value}/{entity}', name: 'cms.api.relation.contents', methods: [GET, HEAD], middleware: [api])
     */
    public function getContentsByRelation(ListRequest $request, string $relation, string $value, string $entity): \Symfony\Component\HttpFoundation\Response
    {
        $payload = ($this->getContentsByRelationAction)($request, $relation, $value, $entity);
        $request->merge($payload);

        return $this->list($request);
    }
}
