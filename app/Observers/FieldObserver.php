<?php

declare(strict_types=1);

namespace Modules\Cms\Observers;

use Modules\Cms\Models\Field;

final class FieldObserver
{
    /**
     * Handle the Field "updating" event.
     */
    public function updating(Field $model): void
    {
        if (property_exists($model, 'pivot') && $model->pivot && $model->pivot->isDirty()) {
            $model->pivot->save();
        }
    }
}
