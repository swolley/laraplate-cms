<?php

declare(strict_types=1);

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Filament\Resources\Contents\Pages\EditContent;
use Modules\CMS\Filament\Resources\Contents\Tables\ContentsTable;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
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
        ->toContain('Valid from:')
        ->toContain($valid_from->format('Y-m-d H:i:s'))
        ->toContain('Valid until:')
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
        ->toContain('Valid from:')
        ->not->toContain('Valid until:');
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
