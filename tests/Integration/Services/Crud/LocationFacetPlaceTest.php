<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Services\Crud\CrudService;
use Modules\Core\Services\Crud\DTOs\FacetPage;
use Modules\Core\Services\Crud\DTOs\FacetQuery;
use Modules\Core\Services\Crud\DTOs\FacetSort;

uses(TestCase::class, RefreshDatabase::class);

function location_facet_list_data(Model $model, Request $request): Modules\Core\Casts\ListRequestData
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

function location_facet(FacetQuery $facet): FacetPage
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

    return app(CrudService::class)->facetValues(location_facet_list_data(new Location, $request), $facet);
}

function location_in(string $country): Location
{
    return Location::factory()->create(['country' => $country]);
}

it('facets locations by the country reached through the place relation', function (): void {
    location_in('Italy');
    location_in('Italy');
    location_in('France');

    $page = location_facet(new FacetQuery(
        groupBy: 'place.country',
        sort: FacetSort::CountDesc,
    ));

    $byKey = collect($page->values)->keyBy('key');

    expect($byKey['Italy']['count'])->toBe(2)
        ->and($byKey['Italy']['total'])->toBe(2)
        ->and($byKey['Italy']['attributes'])->toBe(['place.country' => 'Italy'])
        ->and($byKey['France']['count'])->toBe(1);
});

it('rejects a facet on a magic-accessor column with a clear error', function (): void {
    location_in('Italy');

    // `country` looks like a Location attribute but is a HasPlace accessor over the
    // places table — not a real locations column, so faceting on it must fail fast.
    expect(fn (): FacetPage => location_facet(new FacetQuery(groupBy: 'country')))
        ->toThrow(InvalidArgumentException::class, 'does not exist');
});

it('searches the locations country facet', function (): void {
    location_in('Italy');
    location_in('France');

    $page = location_facet(new FacetQuery(
        groupBy: 'place.country',
        search: 'Ita',
    ));

    expect($page->distinctValues)->toBe(1)
        ->and(collect($page->values)->pluck('key')->all())->toBe(['Italy']);
});
