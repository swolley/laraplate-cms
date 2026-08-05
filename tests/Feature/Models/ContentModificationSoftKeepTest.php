<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Disapproval;
use Modules\Core\Models\Modification;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

it('configures soft-keep flags so content modifications are not deleted', function (): void {
    $content = new Content;

    $delete_when_disapproved = new ReflectionProperty($content, 'deleteWhenDisapproved');
    $delete_when_disapproved->setAccessible(true);
    $delete_when_approved = new ReflectionProperty($content, 'deleteWhenApproved');
    $delete_when_approved->setAccessible(true);

    expect($delete_when_disapproved->getValue($content))->toBeFalse()
        ->and($delete_when_approved->getValue($content))->toBeFalse();
});

it('keeps inactive modification after disapprove with readable reason', function (): void {
    $user = User::factory()->create();
    $content = new Content;

    $modification = Modification::query()->create([
        'modifiable_type' => Content::class,
        'modifiable_id' => 1,
        'modifier_id' => $user->id,
        'modifier_type' => User::class,
        'active' => true,
        'is_update' => true,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('content-soft-keep-disapprove'),
        'modifications' => [
            'title' => ['original' => 'Old', 'modified' => 'New'],
        ],
    ]);

    Disapproval::query()->create([
        'modification_id' => $modification->id,
        'disapprover_id' => $user->id,
        'disapprover_type' => User::class,
        'reason' => 'Needs clearer lead',
    ]);

    $content->applyModificationChanges($modification, false);

    $modification->refresh();

    expect(Modification::query()->whereKey($modification->id)->exists())->toBeTrue()
        ->and((bool) $modification->active)->toBeFalse()
        ->and($modification->disapprovals()->latest('id')->value('reason'))->toBe('Needs clearer lead');
});
