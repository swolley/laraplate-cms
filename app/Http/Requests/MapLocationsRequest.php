<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CMS\Services\Map\BoundingBox;

/**
 * Optional viewport for the map endpoint. All four edges are supplied together or
 * not at all; when present they scope the query to what the map currently shows.
 *
 * @property ?numeric $south
 * @property ?numeric $west
 * @property ?numeric $north
 * @property ?numeric $east
 */
final class MapLocationsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'south' => ['nullable', 'required_with:west,north,east', 'numeric', 'between:-90,90'],
            'north' => ['nullable', 'required_with:south,west,east', 'numeric', 'between:-90,90', 'gte:south'],
            'west' => ['nullable', 'required_with:south,north,east', 'numeric', 'between:-180,180'],
            'east' => ['nullable', 'required_with:south,west,north', 'numeric', 'between:-180,180', 'gte:west'],
        ];
    }

    /** The viewport to scope by, or null when no bounds were supplied. */
    public function bounds(): ?BoundingBox
    {
        if (! $this->filled('south') || ! $this->filled('west') || ! $this->filled('north') || ! $this->filled('east')) {
            return null;
        }

        return new BoundingBox(
            south: (float) $this->input('south'),
            west: (float) $this->input('west'),
            north: (float) $this->input('north'),
            east: (float) $this->input('east'),
        );
    }
}
