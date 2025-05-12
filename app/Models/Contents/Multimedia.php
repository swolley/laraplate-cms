<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Contents;

use Modules\Cms\Models\Content;
use Parental\HasParent;

/**
 * @mixin IdeHelperMultimedia
 */
final class Multimedia extends Content
{
    use HasParent;
}
