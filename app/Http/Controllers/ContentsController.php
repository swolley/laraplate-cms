<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Modules\CMS\Actions\Contents\GetContentsByRelationAction;
use Modules\Core\Http\Controllers\CrudController;
use Modules\Core\Http\Requests\ListRequest;
use Modules\Core\Services\Crud\CrudService;
use Symfony\Component\HttpFoundation\Response;

final class ContentsController extends CrudController
{
    public function __construct(
        private readonly GetContentsByRelationAction $getContentsByRelationAction,
        CrudService $crudService,
    ) {
        parent::__construct($crudService);
    }

    public function getContentsByRelation(ListRequest $request, string $relation, string $value, string $entity): Response
    {
        $payload = ($this->getContentsByRelationAction)($request, $relation, $value, $entity);
        $request->merge($payload);

        // The relation filters are derived from the route only after the request has
        // already been validated, so the cached validator (and therefore validated())
        // never sees them. Rebuild the validator over the merged input so the injected
        // filters reach parsed()/list() instead of being silently dropped.
        $request->setValidator(Validator::make($request->all(), $request->rules()));

        return $this->list($request);
    }
}
