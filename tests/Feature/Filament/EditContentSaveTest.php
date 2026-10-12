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

it('writes the translated fields and the order that the form edits', function (): void {
    $content = createMinimalTestContentForComments();
    $content->forceFill(['valid_from' => now()->subWeek(), 'valid_to' => now()->subDay()])->saveQuietly();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'update']);

    Livewire::test(EditContent::class, ['record' => $content->getKey()])
        ->fillForm([
            'presettable_id' => $content->presettable_id,
            'title' => 'Edited in the panel',
            'components' => '[]',
            'slug' => 'edited-in-the-panel',
            'order_column' => 55,
        ])
        ->call('save')
        ->assertNotified('Saved');

    $translation = ContentTranslation::query()->where('content_id', $content->getKey())->firstOrFail();

    expect($translation->title)->toBe('Edited in the panel')
        ->and($translation->slug)->toBe('edited-in-the-panel')
        ->and($content->fresh()->order_column)->toBe(55);
});
