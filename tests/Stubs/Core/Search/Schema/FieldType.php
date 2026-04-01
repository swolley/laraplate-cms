<?php

declare(strict_types=1);

namespace Modules\Core\Search\Schema;

enum FieldType: string
{
    case TEXT = 'text';
    case KEYWORD = 'keyword';
    case ARRAY = 'array';
    case INTEGER = 'integer';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case VECTOR = 'vector';
    case GEOCODE = 'geocode';
}
