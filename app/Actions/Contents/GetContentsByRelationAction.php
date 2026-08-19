<?php

declare(strict_types=1);

namespace Modules\CMS\Actions\Contents;

use Illuminate\Database\Eloquent\Builder;
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

        $related_ids = $this->resolveRelatedIds($relation, $value);

        $filters[0]['filters'][] = [
            'operator' => WhereClause::And->value,
            'filters' => [
                [
                    'property' => sprintf('%s.id', $relation),
                    'value' => $related_ids,
                    'operator' => FilterOperator::In->value,
                ],
            ],
        ];

        return $filters;
    }

    /**
     * Resolve the related-model ids whose (possibly translated) name OR slug equals
     * the requested value. Taxonomies, tags and contributors store name/slug in a
     * translations table, so the Core QueryBuilder cannot resolve `contents.{relation}.name`
     * to a real column; matching by id sidesteps that. An empty result restricts the
     * outer `whereIn(...)` to no rows, so an unknown value yields an empty response
     * instead of every content.
     *
     * @return list<int|string>
     */
    private function resolveRelatedIds(string $relation, string $value): array
    {
        $related = new Content()->{$relation}()->getRelated();

        $translatable = method_exists($related, 'getTranslatableFields')
            ? $related::getTranslatableFields()
            : [];

        /** @var list<int|string> $ids */
        $ids = $related->newQuery()
            ->where(static function (Builder $query) use ($value, $translatable): void {
                foreach (['name', 'slug'] as $field) {
                    if (in_array($field, $translatable, true)) {
                        $query->orWhereHas('translations', static fn (Builder $translations): Builder => $translations->where($field, $value));

                        continue;
                    }

                    $query->orWhere($field, $value);
                }
            })
            ->pluck($related->getKeyName())
            ->all();

        return $ids;
    }
}
