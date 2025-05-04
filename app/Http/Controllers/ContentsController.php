<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Illuminate\Support\Str;
use Modules\Cms\Models\Content;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Http\Requests\ListRequest;
use Modules\Core\Http\Controllers\CrudController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

final class ContentsController extends CrudController
{
    /**
     * @route-comment
     * Route(path: 'api/v1/{relation}/{value}/{entity}', name: 'cms.api.relation.contents', methods: [GET, HEAD], middleware: [api])
     */
    public function getContentsByRelation(ListRequest $request, string $relation, string $value, string $entity)
    {
        $filters = $this->createCommonFilters($request, $relation, $value);

        if ($entity !== 'contents') {
            $entity = Str::singular($entity);
            $filters['filters'][] = [
                'operator' => WhereClause::OR->value,
                'filters' => [
                    [
                        'property' => 'contents.entity.name',
                        'value' => $entity,
                        'operator' => FilterOperator::EQUALS->value,
                    ],
                    [
                        'property' => 'contents.entity.slug',
                        'value' => $entity,
                        'operator' => FilterOperator::EQUALS->value,
                    ],
                ],
            ];
        }

        $request->merge([
            'entity' => 'contents',
            'filters' => $filters,
        ]);

        return $this->list($request);
    }

    private function createCommonFilters(ListRequest $request, string $relation, string $value): array
    {
        $filters = [
            [
                'operator' => WhereClause::AND->value,
                'filters' => $request->get('filters') ?? [],
            ],
        ];

        if (! method_exists(Content::class, $relation)) {
            $relation = Str::endsWith($relation, 's') ? Str::singular($relation) : Str::plural($relation);
        }

        if (! method_exists(Content::class, $relation)) {
            throw new BadRequestException('Invalid relation');
        }

        $filters[0]['filters'][] = [
            'operator' => WhereClause::OR->value,
            'filters' => [
                [
                    'property' => "contents.{$relation}.name",
                    'value' => $value,
                    'operator' => FilterOperator::EQUALS->value,
                ],
                [
                    'property' => "contents.{$relation}.slug",
                    'value' => $value,
                    'operator' => FilterOperator::EQUALS->value,
                ],
            ],
        ];

        return $filters;
    }
}
