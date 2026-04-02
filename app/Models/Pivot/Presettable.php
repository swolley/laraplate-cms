<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Pivot;

use Modules\Core\Models\Pivot\Presettable as CorePresettable;

/**
 * CMS presettable pivot; behaviour lives in Core — this class exists for the CMS namespace.
 */
final class Presettable extends CorePresettable {}
