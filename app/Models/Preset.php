<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Modules\Core\Models\Preset as CorePreset;
use Override;

/**
 * CMS preset model; behaviour lives in Core — this class exists for the CMS namespace and Filament resources.
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
        return parent::newBaseQueryBuilder()->has('entity');
    }

    #[Override]
    protected function getRelatedContentModelClass(): string
    {
        return Content::class;
    }
}
