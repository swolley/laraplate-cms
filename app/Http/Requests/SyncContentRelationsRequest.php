<?php

declare(strict_types=1);

namespace Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the id lists used to sync a content's many-to-many relations.
 *
 * Each relation key is optional: an absent key leaves that relation untouched,
 * while an explicit (possibly empty) array replaces it wholesale.
 */
final class SyncContentRelationsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer'],
            'locations' => ['sometimes', 'array'],
            'locations.*' => ['integer'],
            'contributors' => ['sometimes', 'array'],
            'contributors.*' => ['integer'],
        ];
    }
}
