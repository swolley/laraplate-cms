<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Modules\CMS\Actions\Contents\GetContentsByRelationAction;
use Modules\Core\Http\Controllers\CrudController;
use Modules\Core\Http\Requests\ListRequest;
use Modules\Core\Services\Crud\CrudService;

final class ContentsController extends CrudController
{
    public function __construct(
        private readonly GetContentsByRelationAction $getContentsByRelationAction,
        CrudService $crudService,
    ) {
        parent::__construct($crudService);
    }

    public function getContentsByRelation(ListRequest $request, string $relation, string $value, string $entity): \Symfony\Component\HttpFoundation\Response
    {
        $payload = ($this->getContentsByRelationAction)($request, $relation, $value, $entity);
        $request->merge($payload);

        return $this->list($request);
    }
}
