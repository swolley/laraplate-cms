<?php

declare(strict_types=1);

namespace Modules\Core\Search\Schema;

enum IndexType: string
{
    case SEARCHABLE = 'searchable';
    case FILTERABLE = 'filterable';
    case FACETABLE = 'facetable';
    case SORTABLE = 'sortable';
    case VECTOR = 'vector';
}
