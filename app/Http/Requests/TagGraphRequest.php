<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tuning for the tag co-occurrence graph endpoint.
 *
 * @property ?numeric $minCoOccurrence
 * @property ?numeric $maxNodes
 */
final class TagGraphRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'minCoOccurrence' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'maxNodes' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function minCoOccurrence(): int
    {
        return $this->filled('minCoOccurrence') ? (int) $this->input('minCoOccurrence') : 1;
    }

    public function maxNodes(): int
    {
        return $this->filled('maxNodes') ? (int) $this->input('maxNodes') : 200;
    }
}
