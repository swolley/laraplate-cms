<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\CMS\Models\Content;
use Modules\Core\Search\Contracts\IOwnerAuthorizer;
use Modules\Core\Services\Authorization\AuthorizationService;
use Modules\Core\Support\PermissionName;
use Override;

/**
 * A media attached to a content is visible to whoever may see the content (M16): the
 * content must be currently valid and pass the reader's `select` ACL, the same recipe the
 * CMS application-content retrieval uses.
 */
final readonly class ContentOwnerAuthorizer implements IOwnerAuthorizer
{
    public function __construct(private AuthorizationService $authorization) {}

    #[Override]
    public function ownerType(): string
    {
        return Content::class;
    }

    #[Override]
    public function visibleOwners(): Builder
    {
        $query = Content::query()->valid();

        $this->authorization->applyAclFiltersToQuery($query, PermissionName::forClass(Content::class, 'select'));

        return $query;
    }
}
