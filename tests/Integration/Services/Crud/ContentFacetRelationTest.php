<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Services\Crud\CrudService;
use Modules\Core\Services\Crud\DTOs\FacetPage;
use Modules\Core\Services\Crud\DTOs\FacetQuery;
use Modules\Core\Services\Crud\DTOs\FacetSort;

uses(TestCase::class, RefreshDatabase::class);

function content_facet_list_data(Model $model, Request $request): Modules\Core\Casts\ListRequestData
{
    $data = new ReflectionClass(Modules\Core\Casts\ListRequestData::class)->newInstanceWithoutConstructor();

    $set = static function (object $obj, string $prop, mixed $value): void {
        new ReflectionProperty($obj, $prop)->setValue($obj, $value);
    };

    foreach ([
        'request' => $request, 'mainEntity' => $model->getTable(), 'primaryKey' => $model->getKeyName(),
        'connection' => $model->getConnectionName(), 'model' => $model, 'columns' => [], 'relations' => [],
        'sort' => [], 'filters' => null, 'group_by' => [], 'pagination' => 25, 'page' => null,
        'skip' => null, 'take' => null, 'from' => null, 'to' => null, 'limit' => null, 'count' => false,
    ] as $prop => $value) {
        $set($data, $prop, $value);
    }

    return $data;
}

function content_facet(FacetQuery $facet): FacetPage
{
    $role = Role::factory()->create(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole($role);
    auth()->login($admin);

    $request = new class extends Request
    {
        public function validated(?string $key = null, mixed $default = null): mixed
        {
            return $key === null ? [] : $default;
        }
    };
    $request->setUserResolver(fn (): User => $admin);

    return app(CrudService::class)->facetValues(content_facet_list_data(new Content, $request), $facet);
}

/**
 * Force a deterministic English label onto a factory-made category.
 */
function named_category(string $name): Category
{
    $category = Category::factory()->create();
    $category->translations()->where('locale', 'en')->update(['name' => $name]);

    return $category;
}

/**
 * @param  list<Category>  $categories
 */
function content_with_categories(array $categories): Content
{
    $content = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $content->categories()->sync(collect($categories)->pluck('id')->all());

    return $content;
}

function content_facet_by_label(FacetPage $page): Illuminate\Support\Collection
{
    return collect($page->values)->keyBy(fn (array $value): mixed => $value['attributes']['translations.name'] ?? null);
}

it('facets contents by the categories pivot with translated labels', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents, EntityType::Categories]);

    $cinema = named_category('Cinema');
    $sport = named_category('Sport');

    content_with_categories([$cinema]);
    content_with_categories([$cinema]);
    content_with_categories([$sport]);

    $page = content_facet(new FacetQuery(
        groupBy: 'categories',
        relation: 'categories',
        fields: ['translations.name'],
        labelField: 'translations.name',
        sort: FacetSort::LabelAsc,
    ));

    $byLabel = content_facet_by_label($page);

    expect($byLabel['Cinema']['count'])->toBe(2)
        ->and($byLabel['Cinema']['total'])->toBe(2)
        ->and($byLabel['Cinema']['key'])->toBe($cinema->id)
        ->and($byLabel['Sport']['count'])->toBe(1);

    // Label sort orders Cinema before Sport regardless of count.
    $labels = collect($page->values)
        ->map(fn (array $value): mixed => $value['attributes']['translations.name'])
        ->filter(fn (mixed $name): bool => in_array($name, ['Cinema', 'Sport'], true))
        ->values()
        ->all();
    expect($labels)->toBe(['Cinema', 'Sport']);
});

it('facets contents by content type labelled from the entity accessor foreign key', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents, EntityType::Categories]);

    content_with_categories([]);
    content_with_categories([]);

    // entity_id has no BelongsTo on Content (entity is an accessor); the label
    // resolves through the declared facet label source instead.
    $page = content_facet(new FacetQuery(
        groupBy: 'entity_id',
        fields: ['entity.name'],
        labelField: 'entity.name',
        sort: FacetSort::CountDesc,
    ));

    $contentsEntity = Content::query()->first()->entity_id;
    $row = collect($page->values)->firstWhere('key', $contentsEntity);

    expect($row['count'])->toBe(2)
        ->and($row['attributes'])->toBe(['entity.name' => 'contents']);
});

function named_contributor(string $name): Modules\CMS\Models\Contributor
{
    $contributor = Modules\CMS\Models\Contributor::factory()->create();
    // ContributorFactory now seeds a current-locale translation, so update it in
    // place with the desired slug instead of inserting a second row for the locale.
    $contributor->translations()->updateOrCreate(
        ['locale' => 'en'],
        ['slug' => Illuminate\Support\Str::slug($name), 'components' => []],
    );
    Modules\CMS\Models\Contributor::query()->whereKey($contributor->id)->update(['name' => $name]);

    return $contributor->refresh();
}

it('facets contents by the contributors pivot with a base-column label', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents, EntityType::Contributors]);

    $alice = named_contributor('Alice');
    $bob = named_contributor('Bob');

    $c1 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c1->contributors()->sync([$alice->id]);
    $c2 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c2->contributors()->sync([$alice->id]);
    $c3 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c3->contributors()->sync([$bob->id]);

    $page = content_facet(new FacetQuery(
        groupBy: 'contributors',
        relation: 'contributors',
        fields: ['name'],
        labelField: 'name',
        sort: FacetSort::CountDesc,
    ));

    $byName = collect($page->values)->keyBy(fn (array $value): mixed => $value['attributes']['name'] ?? null);

    expect($byName['Alice']['count'])->toBe(2)
        ->and($byName['Alice']['key'])->toBe($alice->id)
        ->and($byName['Bob']['count'])->toBe(1);
});

it('facets contents by the locations pivot with a base-column label', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents]);

    $milan = Modules\CMS\Models\Location::factory()->create(['name' => 'Milan']);
    $rome = Modules\CMS\Models\Location::factory()->create(['name' => 'Rome']);

    $c1 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c1->locations()->sync([$milan->id]);
    $c2 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c2->locations()->sync([$milan->id]);
    $c3 = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
    $c3->locations()->sync([$rome->id]);

    $page = content_facet(new FacetQuery(
        groupBy: 'locations',
        relation: 'locations',
        fields: ['name'],
        labelField: 'name',
        sort: FacetSort::CountDesc,
    ));

    $byName = collect($page->values)->keyBy(fn (array $value): mixed => $value['attributes']['name'] ?? null);

    expect($byName['Milan']['count'])->toBe(2)
        ->and($byName['Milan']['key'])->toBe($milan->id)
        ->and($byName['Rome']['count'])->toBe(1);
});

it('searches contents category facet by the translated label', function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents, EntityType::Categories]);

    $cinema = named_category('Cinema');
    $sport = named_category('Sport');

    content_with_categories([$cinema]);
    content_with_categories([$sport]);

    $page = content_facet(new FacetQuery(
        groupBy: 'categories',
        relation: 'categories',
        fields: ['translations.name'],
        labelField: 'translations.name',
        search: 'Cine',
    ));

    expect($page->distinctValues)->toBe(1)
        ->and(collect($page->values)->pluck('key')->all())->toBe([$cinema->id]);
});
