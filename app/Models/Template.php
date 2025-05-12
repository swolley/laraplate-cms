<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Core\Helpers\HasValidations;
use Modules\Core\Helpers\HasVersions;
use Override;

/**
 * @mixin IdeHelperTemplate
 */
final class Template extends Model
{
    use HasFactory, HasValidations, HasVersions {
        getRules as protected getRulesTrait;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'content',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('templates')->where(function ($query): void {
                    $query->where('deleted_at', null);
                }),
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('templates')->where(function ($query): void {
                    $query->where('deleted_at', null);
                })->ignore($this->id, 'id'),
            ],
        ]);

        return $rules;
    }

    #[Override]
    protected function casts(): array
    {
        return [
            // 'site_id' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
