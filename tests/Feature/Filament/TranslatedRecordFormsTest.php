<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Categories\CategoryResource;
use Modules\CMS\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\CMS\Filament\Resources\Categories\Pages\EditCategory;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Filament\Resources\Contributors\Pages\CreateContributor;
use Modules\CMS\Filament\Resources\Contributors\Pages\EditContributor;
use Modules\CMS\Filament\Resources\Tags\Pages\CreateTag;
use Modules\CMS\Filament\Resources\Tags\Pages\EditTag;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Approvals\Operation;
use Modules\Core\Helpers\LocaleContext;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities();
});

/**
 * @param  class-string<Model>  $model
 */
function translated_record(string $model): Model
{
    if ($model !== Content::class) {
        return $model::factory()->create();
    }

    $content = createMinimalTestContentForComments();
    // A published content is captured by approvals; an expired one is written straight away.
    $content->forceFill(['valid_from' => now()->subWeek(), 'valid_to' => now()->subDay()])->saveQuietly();

    return $content;
}

function translation_of(Model $record, ?string $locale = null): ?Model
{
    return $record->getTranslation($locale ?? LocaleContext::get(), false);
}

it('loads the translated fields of the locale it edits', function (string $page, string $model, array $fields, bool $hasComponents): void {
    $record = translated_record($model);
    $translation = translation_of($record);
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'update']);

    $form = Livewire::test($page, ['record' => $record->getKey()])
        ->assertFormSet(array_combine($fields, array_map(static fn (string $field): mixed => $translation->{$field}, $fields)));

    if ($hasComponents) {
        expect(json_decode((string) $form->get('data.components'), true))->toEqual($translation->components);
    }
})->with([
    'a tag' => [EditTag::class, Tag::class, ['name', 'slug'], false],
    'a category' => [EditCategory::class, Category::class, ['name', 'slug'], true],
    'a contributor' => [EditContributor::class, Contributor::class, [], true],
    'a content' => [EditContent::class, Content::class, ['title', 'slug'], true],
]);

it('saves the translated fields it edits', function (string $page, string $model, array $data): void {
    $record = translated_record($model);
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'update']);

    Livewire::test($page, ['record' => $record->getKey()])
        ->fillForm($data)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Saved');

    $fresh = $model::query()->withoutGlobalScopes()->findOrFail($record->getKey());

    foreach (array_diff_key($data, ['components' => true]) as $field => $value) {
        expect($fresh->{$field})->toBe($value);
    }
})->with([
    'a tag' => [EditTag::class, Tag::class, ['name' => 'Edited', 'slug' => 'edited']],
    'a category' => [EditCategory::class, Category::class, ['name' => 'Edited', 'slug' => 'edited', 'components' => '[]']],
    'a contributor' => [EditContributor::class, Contributor::class, ['name' => 'Edited', 'components' => '[]']],
]);

it('creates a tag with the translated fields', function (): void {
    HttpContext::panelActorWithoutApproval(new Tag, ['select', 'insert']);

    Livewire::test(CreateTag::class)
        ->fillForm(['name' => 'Created', 'slug' => 'created'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::query()->whereHas('translations', fn ($query) => $query->where('slug', 'created'))->sole()->name)->toBe('Created');
});

it('creates a dynamic record with the translated fields', function (string $page, string $model, array $data, string $field): void {
    $existing = $model::factory()->create();
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'insert']);

    Livewire::test($page)
        ->fillForm([
            'dynamic_entity_id' => $existing->presettable->entity_id,
            'dynamic_preset_id' => $existing->presettable->preset_id,
            ...$data,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($model::query()->withoutGlobalScopes()->whereKeyNot($existing->getKey())->sole()->{$field})->toBe('Created');
})->with([
    'a contributor' => [CreateContributor::class, Contributor::class, ['name' => 'Created', 'components' => '[]'], 'name'],
]);

it('reports a category creation that needs approval and goes back to the list', function (): void {
    $existing = Category::factory()->create();
    HttpContext::panelActorWithoutApproval(new Category, ['select', 'insert']);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'dynamic_entity_id' => $existing->presettable->entity_id,
            'dynamic_preset_id' => $existing->presettable->preset_id,
            'name' => 'Created',
            'slug' => 'created',
            'components' => '[]',
            'is_active' => true,
            'valid_from' => now()->subDay()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Creation sent for approval')
        ->assertRedirect(CategoryResource::getUrl('index'));

    expect(Category::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('leaves the fields empty, instead of copying another language, when the locale has no translation yet', function (): void {
    $tag = Tag::factory()->create();
    $tag->translations()->where('locale', 'de')->forceDelete();
    $original = translation_of($tag)->name;
    LocaleContext::set('de');
    HttpContext::panelActorWithoutApproval(new Tag, ['select', 'update']);

    Livewire::test(EditTag::class, ['record' => $tag->getKey()])
        ->assertFormSet(['name' => null, 'slug' => null])
        ->fillForm(['name' => 'Übersetzt', 'slug' => 'uebersetzt'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(translation_of($tag, 'de')->name)->toBe('Übersetzt')
        ->and(translation_of($tag, LocaleContext::getDefault())->name)->toBe($original);
});

it('names the locale it edits', function (): void {
    $tag = Tag::factory()->create();
    LocaleContext::set('de');
    HttpContext::panelActorWithoutApproval(new Tag, ['select', 'update']);

    Livewire::test(EditTag::class, ['record' => $tag->getKey()])->assertSee('Translation: de');
});

it('refuses components that are not JSON', function (): void {
    $category = Category::factory()->create();
    HttpContext::panelActorWithoutApproval(new Category, ['select', 'update']);

    Livewire::test(EditCategory::class, ['record' => $category->getKey()])
        ->fillForm(['name' => 'Edited', 'slug' => 'edited', 'components' => '{not json'])
        ->call('save')
        ->assertHasFormErrors(['components']);
});

it('takes the lease of a category it opens for editing', function (): void {
    $category = Category::factory()->create();
    $actor = HttpContext::panelActorWithoutApproval(new Category, ['select', 'update']);

    Livewire::test(EditCategory::class, ['record' => $category->getKey()])
        ->assertSet('record_is_read_only', false);

    expect($category->fresh()->isLockedBy($actor))->toBeTrue();
});

it('labels and reports a category deletion that needs approval', function (): void {
    $category = Category::factory()->create();
    HttpContext::panelActorWithoutApproval(new Category, ['select', 'update', 'delete', 'forceDelete', 'restore']);

    Livewire::test(EditCategory::class, ['record' => $category->getKey()])
        ->assertActionHasLabel(DeleteAction::class, 'Request deletion')
        ->callAction(DeleteAction::class)
        ->assertNotified('Deletion sent for approval');

    expect($category->fresh()->trashed())->toBeFalse()
        ->and($category->modifications()->activeOnly()->sole()->operation)->toBe(Operation::Delete);
});
