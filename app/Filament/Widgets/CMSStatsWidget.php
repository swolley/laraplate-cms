<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Override;

final class CMSStatsWidget extends BaseWidget
{
    #[Override]
    protected static bool $isLazy = true;

    #[Override]
    protected ?string $pollingInterval = null;

    public function getColumns(): array
    {
        return [
            'md' => 2,
        ];
    }

    protected function getStats(): array
    {
        $data = Cache::remember('filament.dashboard.cms_stats', 60, static fn (): array => [
            'contents' => Content::query()->count(),
            'contributors' => Contributor::query()->count(),
        ]);

        return [
            Stat::make('Contents', $data['contents'])
                ->description('Total contents')
                ->descriptionIcon('heroicon-o-pencil')
                ->color('info'),
            // Stat::make('Categories', Category::query()->count())
            //     ->description('Content categories')
            //     ->descriptionIcon('heroicon-o-folder')
            //     ->color('success'),
            Stat::make('Contributors', $data['contributors'])
                ->description('Total contributors')
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
