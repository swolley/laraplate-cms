<?php

namespace Modules\Cms\Models;

use Illuminate\Validation\Rule;
use Modules\Core\Helpers\HasVersions;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Helpers\HasValidations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperTemplate
 */
class Template extends Model
{
    use HasFactory, HasVersions, HasValidations {
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

    #[\Override]
    protected function casts(): array
    {
        return [
            /*'site_id' => 'integer',*/
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules['create'] = array_merge($rules['create'], [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('templates')->where(function ($query) {
                    $query->where('deleted_at', null);
                })
            ],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('templates')->where(function ($query) {
                    $query->where('deleted_at', null);
                })->ignore($this->id, 'id')
            ],
        ]);
        return $rules;
    }
}
