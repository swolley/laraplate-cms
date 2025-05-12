<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Contents;

use Modules\Cms\Models\Content;
use Parental\HasParent;

/**
 * @mixin IdeHelperSurvey
 */
final class Survey extends Content
{
    use HasParent;
}
