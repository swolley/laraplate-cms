<?php

declare(strict_types=1);

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Filament\Resources\Contents\Pages\ListContents;
use Modules\CMS\Filament\Resources\Contents\Tables\ContentsTable;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Filament\RelationManagers\MediaRelationManager;
use Modules\Core\Filament\Utils\HasTable as HasTableTrait;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! class_exists(App\Models\User::class)) {
        class_alias(User::class, App\Models\User::class);
    }

    setupCMSEntities([EntityType::Contents]);

    /** @var App\Models\User $admin */
    $admin = App\Models\User::query()->create(User::factory()->raw([
        'email' => 'cms-content-admin-' . uniqid() . '@example.com',
        'password' => 'Aa1!FilamentAdminPass',
    ]));

    $admin_role = Role::factory()->create(['name' => 'cms-content-admin-' . uniqid()]);
    $admin->roles()->attach($admin_role);

    $this->actingAs($admin);
});

it('renders the contents validity column html for published rows', function (): void {
    $valid_from = now()->subDay()->startOfSecond();
    $valid_to = now()->addWeek()->startOfSecond();

    $content = Content::factory()->create([
        'valid_from' => $valid_from,
        'valid_to' => $valid_to,
    ]);

    $livewire = $this->createStub(HasTable::class);
    $table = Table::make($livewire);
    $table->query(fn () => Content::query());

    ContentsTable::configure($table);

    $column = $table->getColumns()['validity']->record($content->fresh());

    expect($column->toEmbeddedHtml())
        ->toContain('From:')
        ->toContain($valid_from->format('Y-m-d H:i:s'))
        ->toContain('Until:')
        ->toContain($valid_to->format('Y-m-d H:i:s'));
});

it('mounts the content edit page without unsupported livewire form properties', function (): void {
    $content = Content::factory()->create([
        'valid_from' => now()->subDay(),
        'valid_to' => now()->addWeek(),
    ]);

    Livewire::test(EditContent::class, ['record' => $content->getKey()])
        ->assertSuccessful()
        ->assertSet('data.statistics', null)
        ->assertSet('data.is_locked', null);
});

it('formats validity through the shared table helper', function (): void {
    $content = Content::factory()->create([
        'valid_from' => now()->subDay(),
        'valid_to' => null,
    ]);

    expect(HasTableTrait::formatValidityColumnState($content->fresh()))
        ->toContain('From:')
        ->not->toContain('Until:');
});

it('filters contents by preset through the presettable pivot', function (): void {
    $content = Content::factory()->create();
    $preset_id = (int) $content->presettable->preset_id;

    $livewire = $this->createStub(HasTable::class);
    $table = Table::make($livewire);
    $table->query(fn () => Content::query());

    ContentsTable::configure($table);

    $query = Content::query();
    $table->getFilters()['preset']->apply($query, ['values' => [$preset_id]]);

    expect($query->count())->toBe(1);
});

it('returns no content when filtering by a preset nothing uses', function (): void {
    $content = Content::factory()->create();
    $unused_preset_id = (int) $content->presettable->preset_id + 999;

    $livewire = $this->createStub(HasTable::class);
    $table = Table::make($livewire);
    $table->query(fn () => Content::query());

    ContentsTable::configure($table);

    $query = Content::query();
    $table->getFilters()['preset']->apply($query, ['values' => [$unused_preset_id]]);

    expect($query->count())->toBe(0);
});

it('registers the media curation relation manager', function (): void {
    expect(ContentResource::getRelations())->toContain(MediaRelationManager::class);
});

it('renders the contents list with a query count that does not grow with the rows', function (): void {
    $superadmin_role = Role::query()->firstOrCreate(['name' => config('permission.roles.superadmin'), 'guard_name' => 'web']);
    auth()->user()->roles()->attach($superadmin_role);
    auth()->user()->load('roles');

    $connection = (new Content)->getConnection();
    $count_list_queries = static function (int $expected_rows) use ($connection): int {
        $component = Livewire::test(ListContents::class)->set('tableRecordsPerPage', 25);
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        try {
            $component->call('loadTable')
                ->assertSuccessful()
                ->assertCanSeeTableRecords(Content::query()->get())
                ->assertCountTableRecords($expected_rows);

            return count($connection->getQueryLog());
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    };

    Content::factory()->create();
    $count_list_queries(1);
    $queries_with_one_row = $count_list_queries(1);

    Content::factory()->count(24)->create();
    $queries_with_25_rows = $count_list_queries(25);

    expect($queries_with_25_rows)->toBeLessThanOrEqual($queries_with_one_row);
});
