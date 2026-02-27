<?php

declare(strict_types=1);

namespace Modules\Cms\Observers;

use Modules\Cms\Models\Pivot\Fieldable;
use Modules\Cms\Models\Preset;
use Modules\Cms\Services\PresetVersioningService;

final class FieldableObserver
{
    public function __construct(private PresetVersioningService $versioning) {}

    public function saved(Fieldable $fieldable): void
    {
        $this->createVersionForPreset($fieldable);
    }

    public function deleted(Fieldable $fieldable): void
    {
        $this->createVersionForPreset($fieldable);
    }

    private function createVersionForPreset(Fieldable $fieldable): void
    {
        $preset = Preset::find($fieldable->preset_id);

        if (! $preset) {
            return;
        }

        $this->versioning->createVersion($preset);
    }
}
