<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Actions\Contents\GetContentsByRelationAction;
use Modules\CMS\Actions\Contents\SyncContentRelationsAction;
use Modules\CMS\Http\Requests\SyncContentRelationsRequest;
use Modules\CMS\Models\Content;
use Modules\Core\Helpers\ResponseBuilder;
use Modules\Core\Http\Controllers\CrudController;
use Modules\Core\Http\Requests\ListRequest;
use Modules\Core\Services\Crud\CrudService;
use Symfony\Component\HttpFoundation\Response;

final class ContentsController extends CrudController
{
    public function __construct(
        private readonly GetContentsByRelationAction $getContentsByRelationAction,
        private readonly SyncContentRelationsAction $syncContentRelationsAction,
        CrudService $crudService,
    ) {
        parent::__construct($crudService);
    }

    public function getContentsByRelation(ListRequest $request, string $relation, string $value, string $entity): Response
    {
        $payload = ($this->getContentsByRelationAction)($request, $relation, $value, $entity);
        $request->merge($payload);

        return $this->list($request);
    }

    /**
     * Replace a content's many-to-many relations (tags, categories, locations,
     * contributors) from lists of ids. Editing is allowed regardless of the
     * content's validity window, so drafts and expired contents resolve too.
     */
    public function syncRelations(SyncContentRelationsRequest $request, string $content): Response
    {
        $model = Content::withoutGlobalScope('global_filters')->findOrFail((int) $content);

        try {
            $synced = ($this->syncContentRelationsAction)($request, $model);
        } catch (AuthorizationException $exception) {
            return new ResponseBuilder($request)
                ->setData($exception)
                ->setStatus(Response::HTTP_UNAUTHORIZED)
                ->json();
        }

        Cache::clearByEntity($model);

        return new ResponseBuilder($request)->setData(['relations' => $synced])->json();
    }
}
