<?php

declare(strict_types=1);

namespace Modules\CMS\Observers;

use Modules\CMS\Jobs\GeocodeLocationJob;
use Modules\CMS\Models\Location;
use Modules\Core\Models\Place;

/**
 * Observes Place model events and dispatches geocoding jobs for related Locations
 * when address fields change.
 *
 * Address fields (address, city, province, country) are stored on the Place model
 * via the HasPlace trait, not directly on Location. This observer bridges that gap
 * by watching Place saves and dispatching GeocodeLocationJob for any Location
 * that references the updated Place.
 */
final class PlaceObserver
{
    /**
     * Handle the Place "saved" event.
     *
     * Dispatches a GeocodeLocationJob for each Location linked to this Place
     * when any address field has been modified.
     * Skips newly created Places because the related Location is not yet persisted
     * at that point (handled by {@see LocationObserver}).
     */
    public function saved(Place $place): void
    {
        if ($place->wasRecentlyCreated) {
            return;
        }

        if (! $place->wasChanged(['address', 'city', 'province', 'country'])) {
            return;
        }

        Location::query()
            ->where('place_id', $place->getKey())
            ->each(static function (Location $location): void {
                dispatch(new \Modules\CMS\Jobs\GeocodeLocationJob($location));
            });
    }
}
