<?php

declare(strict_types=1);

namespace Modules\CMS\Actions\Contents;

use Illuminate\Http\Request;
use Modules\CMS\Models\Content;
use Modules\Core\Services\Authorization\AuthorizationService;

/**
 * Syncs a content's many-to-many relations from lists of ids.
 *
 * Only the relation keys present on the request are touched, so a partial
 * payload never clears the relations it omits. Writes run in a single
 * transaction after the caller's 'update' permission on the content is verified.
 */
final class SyncContentRelationsAction
{
    /**
     * @var list<string>
     */
    private const array RELATIONS = ['tags', 'categories', 'locations', 'contributors'];

    public function __construct(private readonly AuthorizationService $authorization) {}

    /**
     * @return array<string, list<int>> the synced ids keyed by relation name
     */
    public function __invoke(Request $request, Content $content): array
    {
        $this->authorization->ensurePermission(
            $request,
            $content->getTable(),
            'update',
            $content->getConnectionName(),
        );

        $synced = [];

        $content->getConnection()->transaction(function () use ($request, $content, &$synced): void {
            foreach (self::RELATIONS as $relation) {
                if (! $request->has($relation)) {
                    continue;
                }

                $ids = $this->normalizeIds($request->input($relation));
                $content->{$relation}()->sync($ids);
                $synced[$relation] = $ids;
            }
        });

        return $synced;
    }

    /**
     * @return list<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $ids)));
    }
}
