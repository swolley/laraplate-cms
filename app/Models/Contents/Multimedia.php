<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Contents;

use Parental\HasParent;
use Modules\Cms\Models\Content;

/**
 * @mixin IdeHelperMultimedia
 */
final class Multimedia extends Content
{
    use HasParent;
}
