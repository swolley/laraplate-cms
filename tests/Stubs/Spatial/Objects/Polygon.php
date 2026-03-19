<?php

declare(strict_types=1);

namespace MatanYadaev\EloquentSpatial\Objects;

final class Polygon
{
    /**
     * @param  array<int, mixed>  $points
     */
    public function __construct(public array $points = []) {}
}
