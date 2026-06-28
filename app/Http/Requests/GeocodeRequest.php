<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $q
 * @property ?string $city
 * @property ?string $province
 * @property ?string $country
 */
final class GeocodeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function geocodeQuery(): string
    {
        return $this->string('q')->toString();
    }

    public function geocodeCity(): ?string
    {
        return $this->filled('city') ? $this->string('city')->toString() : null;
    }

    public function geocodeProvince(): ?string
    {
        return $this->filled('province') ? $this->string('province')->toString() : null;
    }

    public function geocodeCountry(): ?string
    {
        return $this->filled('country') ? $this->string('country')->toString() : null;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return true;
    // }
}
