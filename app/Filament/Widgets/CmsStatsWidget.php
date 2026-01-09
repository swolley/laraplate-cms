<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Cms\Models\Author;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Location;
use Modules\Cms\Models\Tag;

final class CmsStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public function getColumns(): int|array|null
    {
        return [
            'md' => 2,
        ];
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Contents', Content::query()->count())
                ->description('Total contents')
                ->descriptionIcon('heroicon-o-pencil')
                ->color('info'),
            // Stat::make('Categories', Category::query()->count())
            //     ->description('Content categories')
            //     ->descriptionIcon('heroicon-o-folder')
            //     ->color('success'),
            Stat::make('Authors', Author::query()->count())
                ->description('Total authors')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
            // Stat::make('Locations', Location::query()->count())
            //     ->description('Geographic locations')
            //     ->descriptionIcon('heroicon-o-map-pin')
            //     ->color('warning'),
            // Stat::make('Tags', Tag::query()->count())
            //     ->description('Content tags')
            //     ->descriptionIcon('heroicon-o-tag')
            //     ->color('gray'),
        ];
    }
}
