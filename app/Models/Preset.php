<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Modules\Core\Models\Preset as CorePreset;

/**
 * CMS preset model; behaviour lives in Core — this class exists for the CMS namespace and Filament resources.
 */
final class Preset extends CorePreset {}
