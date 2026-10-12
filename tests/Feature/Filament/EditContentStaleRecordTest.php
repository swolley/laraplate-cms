<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The form state a valid save needs: the page opens with the translated fields empty in this test locale.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function validContentFormState(Content $content, array $changes = []): array
{
    return [
        'presettable_id' => $content->presettable_id,
        'title' => 'Comment test content',
        'components' => '[]',
        'slug' => 'comment-test-content',
        ...$changes,
    ];
}

it('refuses a save when the content changed after the form was opened', function (): void {
    $content = createMinimalTestContentForComments();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'update']);
    $page = Livewire::test(EditContent::class, ['record' => $content->getKey()]);
    Content::query()->findOrFail($content->getKey())->forceFill(['order_column' => 7])->saveQuietly();

    $page->fillForm(validContentFormState($content, ['order_column' => 99]))->call('save');

    $page->assertNotified('This record was changed by someone else');
    expect($content->fresh()->order_column)->toBe(7);
});

it('refuses a save that edits only translated fields when the content changed', function (): void {
    $content = createMinimalTestContentForComments();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'update']);
    $page = Livewire::test(EditContent::class, ['record' => $content->getKey()]);
    Content::query()->findOrFail($content->getKey())->forceFill(['order_column' => 7])->saveQuietly();

    $page->fillForm(validContentFormState($content, ['title' => 'Edited title']))->call('save');

    $page->assertNotified('This record was changed by someone else');
    expect(ContentTranslation::query()->where('content_id', $content->getKey())->firstOrFail()->title)
        ->toBe('Comment test content');
});
