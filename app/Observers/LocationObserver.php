<?php

declare(strict_types=1);

namespace Modules\CMS\Observers;

use Modules\CMS\Jobs\GeocodeLocationJob;
use Modules\CMS\Models\Location;

/**
 * Observes Location model events and dispatches geocoding jobs when needed.
 *
 * Note: address fields (address, city, province, country) are stored on the related
 * Place model via the HasPlace trait. Updates to those fields are handled by
 * {@see PlaceObserver}. This observer handles the initial geocoding dispatch
 * when a new Location is created.
 */
final class LocationObserver
{
    /**
     * Handle the Location "saved" event.
     *
     * Dispatches a GeocodeLocationJob when a new Location is created,
     * so that its address fields get geocoded on first save.
     * Subsequent address field changes are handled by {@see PlaceObserver}.
     */
    public function saved(Location $location): void
    {
        if ($location->wasRecentlyCreated) {
            dispatch(new \Modules\CMS\Jobs\GeocodeLocationJob($location));
        }
    }
}
