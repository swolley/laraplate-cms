<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Filament\Widgets\CMSStatsWidget;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Approvals\Operation;
use Modules\Core\Models\Modification;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

it('counts pending moderation, scheduled and expiring contents', function (): void {
    setupCMSEntities([EntityType::Contents]);
    $user = User::factory()->create();

    $pending = static fn (string $type, bool $active, string $seed): Modification => Modification::query()->create([
        'modifiable_type' => (new $type)->getMorphClass(),
        'modifiable_id' => 1,
        'modifier_id' => $user->id,
        'modifier_type' => User::class,
        'active' => $active,
        'operation' => Operation::Update,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5($seed),
        'modifications' => ['title' => ['original' => 'Old', 'modified' => 'New']],
    ]);

    $pending(Content::class, true, 'content-a');
    $pending(Content::class, true, 'content-b');
    $pending(Content::class, false, 'content-closed');
    $pending(Comment::class, true, 'comment-a');

    Content::factory()->create(['valid_from' => now()->addDay(), 'valid_to' => null]);
    Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => now()->addHour()]);
    Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    Cache::flush();

    $method = new ReflectionMethod(CMSStatsWidget::class, 'getStats');
    [$contents, $comments, $scheduled, $expiring] = $method->invoke(new CMSStatsWidget());

    expect($contents->getValue())->toBe(2)
        ->and($comments->getValue())->toBe(1)
        ->and($scheduled->getValue())->toBe(1)
        ->and($expiring->getValue())->toBe(1);
});
