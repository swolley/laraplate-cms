<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Comments\CommentResource;
use Modules\CMS\Filament\Resources\Comments\Pages\EditComment;
use Modules\CMS\Filament\Resources\Comments\Pages\ListComments;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\CMS\Models\Comment;
use Modules\CMS\Models\Translations\CommentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! class_exists(App\Models\User::class)) {
        class_alias(User::class, App\Models\User::class);
    }

    /** @var App\Models\User $actor */
    $actor = App\Models\User::query()->create(User::factory()->raw());
    $actor->assignRole(Role::findOrCreate(config('permission.roles.superadmin'), 'web'));

    $this->actingAs($actor);
    Filament::setCurrentPanel('admin');

    $this->comment = Comment::factory()->approved()->create([
        'content_id' => createMinimalTestContentForComments()->id,
        'user_id' => $actor->id,
    ]);
    CommentTranslation::query()->where('comment_id', $this->comment->id)->update(['locale' => LocaleContext::get(), 'body' => 'A comment worth reading']);
});

it('lists comments with their text', function (): void {
    $list = Livewire::test(ListComments::class)->assertOk();

    expect($list->instance()->getTableRecords()->modelKeys())->toBe([$this->comment->getKey()]);

    $list->assertTableColumnStateSet('body', 'A comment worth reading', $this->comment);
});

it('opens a comment for editing but offers no create page', function (): void {
    Livewire::test(EditComment::class, ['record' => $this->comment->getKey()])
        ->assertOk()
        ->assertFormSet(['body' => 'A comment worth reading']);

    expect(CommentResource::getPages())->not->toHaveKey('create');
});

it('sits right after contents in the CMS navigation', function (): void {
    expect(CommentResource::getNavigationGroup())->toBe(ContentResource::getNavigationGroup())
        ->and(CommentResource::getNavigationSort())->toBe(ContentResource::getNavigationSort() + 1);
});
