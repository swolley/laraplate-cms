<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Location;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class, RefreshDatabase::class);

function locationsUpdateUrl(): string
{
    return route('core.crud.replace', ['module' => 'cms', 'entity' => 'locations']);
}

function locationsSuperadmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(config('permission.roles.superadmin'), 'web'));

    return $user;
}

it('lets a location be saved with its own unique name, only the request-level rules being involved', function (): void {
    $this->artisan('permission:refresh');
    $location = Location::factory()->create(['name' => "San Nazzaro d'Ongina"]);

    $response = $this->actingAs(locationsSuperadmin())->patchJson(locationsUpdateUrl(), [
        'id' => $location->id,
        'name' => "San Nazzaro d'Ongina",
    ]);

    // The record is excluded from its own unique check: not rejected as "already used".
    expect($response->getStatusCode())->not->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    expect($response->json('error') ?? '')->not->toContain('già utilizzato');
});

it('still rejects a name that belongs to another location', function (): void {
    $this->artisan('permission:refresh');
    Location::factory()->create(['name' => 'Piacenza']);
    $other = Location::factory()->create(['name' => 'Cremona']);

    $response = $this->actingAs(locationsSuperadmin())->patchJson(locationsUpdateUrl(), [
        'id' => $other->id,
        'name' => 'Piacenza',
    ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});
