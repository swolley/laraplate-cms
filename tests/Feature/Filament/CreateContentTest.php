<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Contents\Pages\CreateContent;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

it('creates a content with the translated fields that the form edits', function (): void {
    $existing = createMinimalTestContentForComments();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'insert']);

    Livewire::test(CreateContent::class)
        ->fillForm([
            'dynamic_entity_id' => $existing->entity_id,
            'dynamic_preset_id' => $existing->presettable->preset_id,
            'title' => 'Created in the panel',
            'components' => '[]',
            'slug' => 'created-in-the-panel',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $translation = ContentTranslation::query()->where('slug', 'created-in-the-panel')->firstOrFail();

    expect($translation->title)->toBe('Created in the panel')
        ->and($translation->content_id)->not->toBe($existing->getKey());
});

it('does not offer the position when creating, since a new content goes last', function (): void {
    createMinimalTestContentForComments();
    HttpContext::panelActorWithoutApproval(new Content, ['select', 'insert']);

    Livewire::test(CreateContent::class)
        ->assertFormFieldHidden('order_column');
});
