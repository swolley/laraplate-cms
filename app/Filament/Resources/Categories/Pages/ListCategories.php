<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\Cms\Filament\Resources\Categories\CategoryResource;
use Modules\Cms\Filament\Utils\HasRecords;
use Modules\Cms\Models\Category;

final class ListCategories extends ListRecords
{
    use HasRecords;

    protected static string $resource = CategoryResource::class;

    protected function getTableQuery(): Builder
    {
        /** @var class-string<Category> $model */
        $model = self::getResource()::getModel();

        return $model::query()->withAncestorsForPath();
    }
}
