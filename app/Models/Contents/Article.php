<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Contents;

use Parental\HasParent;
use Modules\Cms\Models\Content;

/**
 * @mixin IdeHelperArticle
 */
final class Article extends Content
{
    use HasParent;
}
