<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Casts\Filter;
use Modules\Core\Casts\FilterOperator;
use Modules\Core\Casts\FiltersGroup;
use Modules\Core\Casts\WhereClause;
use Modules\Core\Models\ACL;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Core\Services\Authorization\AuthorizationService;
use Modules\Core\Support\PermissionName;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Mirror the CMS seeder: the guest role gets a role-scoped validity ACL on the
 * exact `contents.select` permission the CRUD checks.
 *
 * @return array{Permission, Role, string}
 */
function seedGuestContentAcl(): array
{
    $content = new Content;
    $permission_name = PermissionName::build(
        $content->getConnectionName() ?? 'default',
        $content->getTable(),
        'select',
    );

    $permission = Permission::firstOrCreate(['name' => $permission_name], ['guard_name' => 'web']);

    $guest = Role::findOrCreate((string) config('permission.roles.guest'), 'web');
    $guest->givePermissionTo($permission);

    $table = $content->getTable();
    $filters = new FiltersGroup(
        filters: [
            new Filter("{$table}.valid_from", '@now', FilterOperator::LessEquals),
            new FiltersGroup(
                filters: [
                    new Filter("{$table}.valid_to", '@now', FilterOperator::GreatEquals),
                    new Filter("{$table}.valid_to", null, FilterOperator::Equals),
                ],
                operator: WhereClause::Or,
            ),
        ],
        operator: WhereClause::And,
    );

    $acl = new ACL;
    $acl->setSkipValidation(true);
    $acl->forceFill([
        'permission_id' => $permission->id,
        'role_id' => $guest->id,
        'filters' => $filters,
        'priority' => 100,
        'is_active' => true,
    ]);
    $acl->save();

    return [$permission, $guest, $permission_name];
}

/**
 * @return list<int>
 */
function aclFilteredContentIds(string $permissionName): array
{
    $query = Content::query();
    app(AuthorizationService::class)->applyAclFiltersToQuery($query, $permissionName);

    return $query->pluck('id')->all();
}

afterEach(function (): void {
    Carbon::setTestNow();
});

it('limits a guest to published contents through the role-scoped validity ACL', function (): void {
    Carbon::setTestNow('2026-08-19 12:00:00');
    setupCMSEntities([EntityType::Contents]);
    [, $guest, $permission_name] = seedGuestContentAcl();

    $draft = Content::factory()->create(['valid_from' => now()->addWeek(), 'valid_to' => null]);
    $live = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    $guest_user = User::factory()->create();
    $guest_user->assignRole($guest);
    Auth::login($guest_user);

    $ids = aclFilteredContentIds($permission_name);

    expect($ids)->toContain($live->id)
        ->and($ids)->not->toContain($draft->id);
});

it('does not restrict a staff role that has no validity ACL on the same permission', function (): void {
    Carbon::setTestNow('2026-08-19 12:00:00');
    setupCMSEntities([EntityType::Contents]);
    [$permission, , $permission_name] = seedGuestContentAcl();

    $staff = Role::findOrCreate('cms_staff_' . uniqid(), 'web');
    $staff->givePermissionTo($permission);

    $draft = Content::factory()->create(['valid_from' => now()->addWeek(), 'valid_to' => null]);
    $live = Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);

    $staff_user = User::factory()->create();
    $staff_user->assignRole($staff);
    Auth::login($staff_user);

    $ids = aclFilteredContentIds($permission_name);

    expect($ids)->toContain($live->id)
        ->and($ids)->toContain($draft->id);
});

it('leaves the validity out of the global scope so a direct query returns drafts', function (): void {
    setupCMSEntities([EntityType::Contents]);

    $draft = Content::factory()->create(['valid_from' => now()->addWeek(), 'valid_to' => null]);

    expect(Content::query()->whereKey($draft->id)->exists())->toBeTrue();
});
