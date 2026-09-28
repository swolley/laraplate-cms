<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Approvals\Operation;
use Modules\Core\Models\Disapproval;
use Modules\Core\Models\Modification;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

it('keeps inactive modification after approve', function (): void {
    $user = User::factory()->create();
    $content = createMinimalTestContentForComments();

    $modification = Modification::query()->create([
        'modifiable_type' => Content::class,
        'modifiable_id' => $content->id,
        'modifier_id' => $user->id,
        'modifier_type' => User::class,
        'active' => true,
        'operation' => Operation::Update,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('content-soft-keep-approve'),
        'modifications' => [],
    ]);

    $content->applyModificationChanges($modification, true);

    expect(Modification::query()->whereKey($modification->id)->exists())->toBeTrue()
        ->and((bool) $modification->fresh()->active)->toBeFalse();
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
        'operation' => Operation::Update,
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

it('deletes a draft directly and sends the deletion of a live content for approval', function (): void {
    $live = createMinimalTestContentForComments();
    $draft = createMinimalTestContentForComments();
    $draft->valid_from = null;
    $draft->save();

    Modules\Core\Tests\Support\HttpContext::pretendHttpRequest();
    $this->actingAs(User::factory()->create());

    expect($draft->delete())->toBeTrue()
        ->and($draft->pendingModification())->toBeNull()
        ->and($live->delete())->toBeFalse()
        ->and($live->pendingModification()?->operation)->toBe(Operation::Delete)
        ->and(Content::query()->whereKey($live->id)->exists())->toBeTrue();
});
