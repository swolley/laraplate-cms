<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Approvals\Operation;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

it('labels and reports a deletion that needs approval', function (): void {
    $content = createMinimalTestContentForComments();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'update', 'delete', 'forceDelete', 'restore']);

    Livewire::test(EditContent::class, ['record' => $content->getKey()])
        ->assertActionHasLabel(DeleteAction::class, 'Request deletion')
        ->callAction(DeleteAction::class)
        ->assertNotified('Deletion sent for approval');

    expect($content->fresh()->trashed())->toBeFalse()
        ->and($content->modifications()->activeOnly()->sole()->operation)->toBe(Operation::Delete);
});
