<?php

declare(strict_types=1);

namespace Modules\CMS\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Hides extended contents (`extended_type IS NOT NULL`) from every default read path.
 *
 * A content that is the body of a module extender (see {@see \Modules\CMS\Contracts\ExtendsContent})
 * must not leak into generic CMS surfaces. Opt back in with `Content::withExtended()`, which removes
 * only this scope and leaves soft-delete and any other global scope in place.
 */
final class HidesExtendedContent implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->qualifyColumn('extended_type'));
    }
}
