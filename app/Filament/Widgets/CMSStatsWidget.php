<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\Core\Models\Modification;
use Override;

/**
 * Editorial work waiting for someone: changes and comments to moderate, contents
 * about to go live and contents about to expire.
 */
final class CMSStatsWidget extends BaseWidget
{
    #[Override]
    protected static ?int $sort = 20;

    #[Override]
    protected ?string $heading = 'CMS';

    #[Override]
    protected static bool $isLazy = true;

    #[Override]
    protected ?string $pollingInterval = null;

    public function getColumns(): array
    {
        return [
            'md' => 4,
        ];
    }

    protected function getStats(): array
    {
        $data = Cache::remember('filament.dashboard.cms_stats', 60, static fn (): array => [
            'pending_contents' => self::pendingModifications(new Content()),
            'pending_comments' => self::pendingModifications(new Comment()),
            'scheduled' => Content::query()->scheduled()->count(),
            'expiring' => Content::query()->expiring()->count(),
        ]);

        return [
            Stat::make('Contents to approve', $data['pending_contents'])
                ->description('Changes awaiting moderation')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($data['pending_contents'] > 0 ? 'warning' : 'gray')
                ->descriptionColor('cms'),
            Stat::make('Comments to moderate', $data['pending_comments'])
                ->description('Comments awaiting moderation')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color($data['pending_comments'] > 0 ? 'warning' : 'gray')
                ->descriptionColor('cms'),
            Stat::make('Scheduled', $data['scheduled'])
                ->description('Contents waiting to go live')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info')
                ->descriptionColor('cms'),
            Stat::make('Expiring soon', $data['expiring'])
                ->description('Published contents about to expire')
                ->descriptionIcon('heroicon-o-clock')
                ->color($data['expiring'] > 0 ? 'warning' : 'gray')
                ->descriptionColor('cms'),
        ];
    }

    private static function pendingModifications(Content|Comment $model): int
    {
        return Modification::query()
            ->activeOnly()
            ->where('modifiable_type', $model->getMorphClass())
            ->count();
    }
}
