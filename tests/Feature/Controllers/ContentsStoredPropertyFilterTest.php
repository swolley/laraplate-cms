<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Pivot\Presettable;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\FieldType as CoreFieldType;
use Modules\Core\Models\Field;
use Modules\Core\Models\Role;
use Modules\Core\Services\DynamicContentsService;
use Modules\Core\Services\PresetVersioningService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    setupCMSEntities();

    $role = Role::factory()->create(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);
    $this->user = $user;
});

/**
 * @param  array<string, mixed>  $filter
 * @return list<int>
 */
function filtered_content_ids(array $filter): array
{
    $response = test()->postJson(route('core.crud.list', ['module' => 'cms', 'entity' => 'contents']), [
        'pagination' => 100,
        'page' => 1,
        'filters' => ['operator' => 'and', 'filters' => [$filter]],
    ]);
    $response->assertOk();

    return collect($response->json('data'))->pluck('id')->sort()->values()->all();
}

function content_titled(string $title, string $locale = 'en'): Content
{
    $content = Content::factory()->create();
    $content->setTranslation($locale, ['title' => $title, 'slug' => str($title)->slug()->toString(), 'components' => []]);
    $content->save();

    return $content;
}

/**
 * Gives the contents' preset two dynamic fields, one translated and one shared, and publishes it.
 *
 * @return array{translated: string, shared: string}
 */
function content_dynamic_fields(Content $content): array
{
    $names = ['translated' => 'dyn_translated_' . uniqid(), 'shared' => 'dyn_shared_' . uniqid()];

    foreach ($names as $kind => $name) {
        $field = Field::query()->create(['name' => $name, 'type' => CoreFieldType::Text, 'options' => new stdClass()]);
        $field->is_translatable = $kind === 'translated';
        $field->save();
        $content->preset->fields()->attach($field->id, ['default' => null, 'is_required' => false]);
    }

    resolve(PresetVersioningService::class)->createVersion($content->preset);
    DynamicContentsService::getInstance()->clearAllCaches();

    return $names;
}

function with_latest_presettable(Content $content): Content
{
    $content->setRelation('presettable', Presettable::query()
        ->where('entity_id', $content->entity_id)
        ->where('preset_id', $content->preset->id)
        ->whereNull('deleted_at')
        ->latest('version')
        ->firstOrFail());

    return $content;
}

it('filters on a translated field by its plain name, in the locale of the request', function (): void {
    $english = content_titled('Moon landing');
    content_titled('Sea trip');
    $italian_only = content_titled('Allunaggio', 'it');

    expect(filtered_content_ids(['property' => 'title', 'operator' => 'like', 'value' => '%Moon%']))->toBe([$english->id])
        ->and(filtered_content_ids(['property' => 'title', 'operator' => 'like', 'value' => '%Allunaggio%']))->toBe([]);

    // The locale of a request is its user's language.
    $this->user->forceFill(['lang' => 'it'])->save();

    expect(filtered_content_ids(['property' => 'title', 'operator' => 'like', 'value' => '%Allunaggio%']))->toBe([$italian_only->id]);
});

it('filters on dynamic fields wherever they are kept, shared or translated', function (): void {
    $probe = Content::factory()->create();
    $fields = content_dynamic_fields($probe);

    $first = with_latest_presettable(content_titled('First'));
    $first->{$fields['shared']} = 'red';
    $first->{$fields['translated']} = 'rosso';
    $first->save();

    $second = with_latest_presettable(content_titled('Second'));
    $second->{$fields['shared']} = 'blue';
    $second->{$fields['translated']} = 'blu';
    $second->save();

    expect(filtered_content_ids(['property' => $fields['shared'], 'operator' => 'eq', 'value' => 'red']))->toBe([$first->id])
        ->and(filtered_content_ids(['property' => $fields['translated'], 'operator' => 'eq', 'value' => 'blu']))->toBe([$second->id])
        ->and(filtered_content_ids(['property' => $fields['translated'], 'operator' => 'eq', 'value' => 'red']))->toBe([]);
});

it('keeps a real column resolved as a column', function (): void {
    $content = content_titled('Plain');

    expect(filtered_content_ids(['property' => 'id', 'operator' => 'eq', 'value' => $content->id]))->toBe([$content->id]);
});
