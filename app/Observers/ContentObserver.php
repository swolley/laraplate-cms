<?php

declare(strict_types=1);

namespace Modules\Cms\Observers;

use Modules\Cms\Models\Content;

final class ContentObserver
{
    /**
     * Handle the Content "creating" event.
     */
    public function creating(Content $model): void
    {
        // Auto-assign entity and preset if not already set
        if (($model->entity_id === null || $model->presettable_id === null)) {
            $model->setDefaultEntityAndPreset();
        }
    }
}
