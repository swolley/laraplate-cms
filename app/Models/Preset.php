<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Models\Preset as CorePreset;
use Override;

/**
 * CMS preset model; behaviour lives in Core — this class exists for the CMS namespace and Filament resources.
 *
 * @mixin \Eloquent
 * @mixin IdeHelperPreset
 */
final class Preset extends CorePreset
{
    /**
     * @return HasManyThrough<Content>
     */
    public function contents(): HasManyThrough
    {
        return $this->hasManyThrough(
            self::getRelatedModelClass(),
            Presettable::class,
            'preset_id',      // foreign key on presettables pointing to presets
            'presettable_id', // foreign key on contents pointing to presettables
        );
    }

    /**
     * Migrate all contents to the latest presettable version.
     * This reassigns every content's presettable_id to the current active version.
     */
    public function migrateContentsToLastVersion(): void
    {
        $this->migrateRelatedModelsToLastVersion();
    }

    #[Override]
    protected static function getRelatedModelClass(): string
    {
        return Content::class;
    }

    #[Override]
    protected function newBaseQueryBuilder(): Builder
    {
        return parent::newBaseQueryBuilder()->whereExists(function (Builder $query): void {
            $entities_table = CoreTables::Entities->value;
            $query->select(DB::raw('1'))
                ->from($entities_table)
                ->whereIn("{$entities_table}.type", EntityType::values());
        });
    }
}
