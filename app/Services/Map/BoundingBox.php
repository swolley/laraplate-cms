<?php

declare(strict_types=1);

namespace Modules\CMS\Services\Map;

use InvalidArgumentException;

/**
 * A geographic viewport (WGS84 decimal degrees) used to scope map queries to what
 * is currently visible. Latitudes clamp to [-90, 90], longitudes to [-180, 180];
 * a viewport that crosses the antimeridian (west > east) is not supported here.
 */
final readonly class BoundingBox
{
    public function __construct(
        public float $south,
        public float $west,
        public float $north,
        public float $east,
    ) {
        if ($south > $north) {
            throw new InvalidArgumentException('BoundingBox south must be <= north.');
        }

        if ($west > $east) {
            throw new InvalidArgumentException('BoundingBox west must be <= east (antimeridian crossing unsupported).');
        }
    }

    /**
     * @param array{south?: mixed, west?: mixed, north?: mixed, east?: mixed} $bounds
     */
    public static function fromArray(array $bounds): self
    {
        foreach (['south', 'west', 'north', 'east'] as $edge) {
            if (! isset($bounds[$edge]) || ! is_numeric($bounds[$edge])) {
                throw new InvalidArgumentException(sprintf('BoundingBox is missing a numeric "%s" edge.', $edge));
            }
        }

        return new self(
            (float) $bounds['south'],
            (float) $bounds['west'],
            (float) $bounds['north'],
            (float) $bounds['east'],
        );
    }
}
