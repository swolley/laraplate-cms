<?php

namespace Modules\Cms\Models\Contents;

use Parental\HasParent;
use Modules\Cms\Models\Content;

/**
 * @mixin IdeHelperArticle
 */
class Article extends Content
{
    use HasParent;
}