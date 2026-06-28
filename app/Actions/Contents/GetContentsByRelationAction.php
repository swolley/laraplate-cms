<?php

declare(strict_types=1);

namespace Modules\CMS\Actions\Contents;

use Illuminate\Support\Str;
use Modules\CMS\Models\Content;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Http\Requests\ListRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

final class GetContentsByRelationAction
{
    /**
     * @return array{entity: string, filters: array<int|string, array{operator: string, filters: list<array<string, mixed>>}>}
     */
    public function __invoke(ListRequest $request, string $relation, string $value, string $entity): array
    {
        $filters = $this->createCommonFilters($request, $relation, $value);

        if ($entity !== 'contents') {
            $entity = Str::singular($entity);
            $filters[] = [
                'operator' => WhereClause::Or->value,
                'filters' => [
                    [
                        'property' => 'contents.presettable.entity.name',
                        'value' => $entity,
                        'operator' => FilterOperator::Equals->value,
                    ],
                    [
                        'property' => 'contents.presettable.entity.slug',
                        'value' => $entity,
                        'operator' => FilterOperator::Equals->value,
                    ],
                ],
            ];
        }

        return [
            'entity' => 'contents',
            'filters' => $filters,
        ];
    }

    /**
     * @return array<int|string, array{operator: string, filters: list<array<string, mixed>>}>
     */
    private function createCommonFilters(ListRequest $request, string $relation, string $value): array
    {
        $request_filters = $request->get('filters');
        $nested_filters = [];

        if (is_array($request_filters)) {
            foreach ($request_filters as $filter) {
                if (is_array($filter)) {
                    $nested_filters[] = $filter;
                }
            }
        }

        $filters = [
            [
                'operator' => WhereClause::And->value,
                'filters' => $nested_filters,
            ],
        ];

        if (! method_exists(Content::class, $relation)) {
            $relation = Str::endsWith($relation, 's') ? Str::singular($relation) : Str::plural($relation);
        }

        throw_unless(method_exists(Content::class, $relation), BadRequestException::class, 'Invalid relation');

        $filters[0]['filters'][] = [
            'operator' => WhereClause::Or->value,
            'filters' => [
                [
                    'property' => sprintf('contents.%s.name', $relation),
                    'value' => $value,
                    'operator' => FilterOperator::Equals->value,
                ],
                [
                    'property' => sprintf('contents.%s.slug', $relation),
                    'value' => $value,
                    'operator' => FilterOperator::Equals->value,
                ],
            ],
        ];

        return $filters;
    }
}
