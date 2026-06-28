<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\CMS\Filament\Utils\HasRecords;
use Modules\CMS\Models\Content;
use Override;

final class ListContents extends ListRecords
{
    use HasRecords;

    #[Override]
    protected static string $resource = ContentResource::class;

    /**
     * Eager load relations used by table columns to avoid N+1 (entity.name, preset.name, cover, media).
     *
     * @return Builder<Content>
     */
    public function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }

        if (! $query instanceof Builder) {
            return Content::query()->with([
                'presettable.preset:id,name',
                'presettable.entity:id,name',
                'media',
            ]);
        }

        return $query->with([
            'presettable.preset:id,name',
            'presettable.entity:id,name',
            'media',
        ]);
    }
}
