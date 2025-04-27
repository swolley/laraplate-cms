<?php

namespace Modules\Cms\Models\Contents;

use Modules\Cms\Models\Content;
use Parental\HasParent;

/**
 * @mixin IdeHelperSurvey
 */
class Survey extends Content
{
    use HasParent;
}
