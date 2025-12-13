<?php

declare(strict_types=1);

namespace Modules\Cms\Actions\Contents;

use Illuminate\Support\Str;
use Modules\Cms\Models\Content;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Http\Requests\ListRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

final class GetContentsByRelationAction
{
    public function __invoke(ListRequest $request, string $relation, string $value, string $entity): array
    {
        $filters = $this->createCommonFilters($request, $relation, $value);

        if ($entity !== 'contents') {
            $entity = Str::singular($entity);
            $filters['filters'][] = [
                'operator' => WhereClause::OR->value,
                'filters' => [
                    [
                        'property' => 'contents.presettable.entity.name',
                        'value' => $entity,
                        'operator' => FilterOperator::EQUALS->value,
                    ],
                    [
                        'property' => 'contents.presettable.entity.slug',
                        'value' => $entity,
                        'operator' => FilterOperator::EQUALS->value,
                    ],
                ],
            ];
        }

        return [
            'entity' => 'contents',
            'filters' => $filters,
        ];
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

        throw_unless(method_exists(Content::class, $relation), BadRequestException::class, 'Invalid relation');

        $filters[0]['filters'][] = [
            'operator' => WhereClause::OR->value,
            'filters' => [
                [
                    'property' => sprintf('contents.%s.name', $relation),
                    'value' => $value,
                    'operator' => FilterOperator::EQUALS->value,
                ],
                [
                    'property' => sprintf('contents.%s.slug', $relation),
                    'value' => $value,
                    'operator' => FilterOperator::EQUALS->value,
                ],
            ],
        ];

        return $filters;
    }
}

