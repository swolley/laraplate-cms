<?php

declare(strict_types=1);

namespace MatanYadaev\EloquentSpatial\Objects;

use Illuminate\Database\Eloquent\Model;

final class Point
{
    public function __construct(
        public float $latitude = 0.0,
        public float $longitude = 0.0,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return new self();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof self) {
            return json_encode([
                'latitude' => $value->latitude,
                'longitude' => $value->longitude,
            ]);
        }

        return json_encode([
            'latitude' => 0.0,
            'longitude' => 0.0,
        ]);
    }
}
