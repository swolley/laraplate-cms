<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Cms\Filament\Resources\Contents\ContentResource;
use Modules\Cms\Filament\Utils\HasRecords;

final class ListContents extends ListRecords
{
    use HasRecords;

    protected static string $resource = ContentResource::class;

    /**
     * Eager load relations used by table columns to avoid N+1 (entity.name, preset.name, cover, media).
     */
    public function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with([
                'presettable.preset:id,name',
                'presettable.entity:id,name',
                'media',
            ]);
    }
}
