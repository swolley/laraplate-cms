<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Contents;

use Modules\Cms\Models\Content;
use Parental\HasParent;

/**
 * @mixin IdeHelperArticle
 */
final class Article extends Content
{
    use HasParent;
}
