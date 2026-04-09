<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Cms\Database\Factories\EntityFactory as CmsEntityFactory;
use Modules\Core\Models\Entity as CoreEntity;
use Override;

/**
 * Test stub: minimal Core entity base for CMS module tests without loading the full Core package.
 *
 * @property int|string $id
 * @property string $name
 * @property string $slug
 */
final class Entity extends CoreEntity
{
    public function getRules(): array
    {
        $rules = $this->getRulesTrait();
        $rules[self::DEFAULT_RULE] = array_merge($rules[self::DEFAULT_RULE], [
            'is_active' => 'boolean',
            'slug' => 'sometimes|nullable|string|max:255',
        ]);
        $rules['create'] = array_merge($rules['create'], [
            'name' => ['required', 'string', 'max:255', 'unique:entities,name'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'name' => ['sometimes', 'string', 'max:255', 'unique:entities,name,' . $this->id],
        ]);

        return $rules;
    }

    #[Override]
    protected static function newFactory(): CmsEntityFactory
    {
        return CmsEntityFactory::new();
    }

    #[Override]
    protected static function booted(): void
    {
        self::addGlobalScope('active', static function (Builder $builder): void {
            $builder->active();
        });
    }

    protected function casts(): array
    {
        return array_merge($this->activationCasts(), [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ]);
    }
}
