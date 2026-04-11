<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Casts\EntityType;
use Modules\Core\Models\Pivot\Presettable;
use Modules\Core\Models\Preset as CorePreset;
use Override;

/**
 * CMS preset model; behaviour lives in Core — this class exists for the CMS namespace and Filament resources.
 *
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
            $this->getRelatedContentModelClass(),
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

    /**
     * @return Builder<static>
     */
    #[Override]
    public function newBaseQueryBuilder()
    {
        return parent::newBaseQueryBuilder()->whereExists(function (Builder $query): void {
            $query->select(DB::raw('1'))
                ->from('entities')
                ->whereIn('entities.type', EntityType::values());
        });
    }

    #[Override]
    protected static function getRelatedContentModelClass(): string
    {
        return Content::class;
    }
}
