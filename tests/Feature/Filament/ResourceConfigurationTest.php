<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Filament\Resources\Contributors\ContributorResource;
use Modules\CMS\Filament\Resources\Entities\EntityResource;
use Modules\CMS\Filament\Resources\Locations\LocationResource;
use Modules\CMS\Filament\Resources\Tags\TagResource;
use Modules\CMS\Filament\Resources\Templates\TemplateResource;
use Modules\CMS\Filament\Widgets\CMSStatsWidget;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! class_exists(App\Models\User::class)) {
        class_alias(User::class, App\Models\User::class);
    }

    /** @var App\Models\User $admin */
    $admin = App\Models\User::query()->create(User::factory()->raw([
        'email' => 'cms-admin-' . uniqid() . '@example.com',
        'password' => 'Aa1!FilamentAdminPass',
    ]));
    $this->admin = $admin;

    $admin_role = Role::factory()->create(['name' => 'cms-admin-' . uniqid()]);
    $this->admin->roles()->attach($admin_role);
});

it('exposes expected slugs for cms resources', function (): void {
    expect(EntityResource::getSlug())->toBe('cms/entities')
        ->and(EntityResource::getRelations())->toBe([]);

    expect(TemplateResource::getSlug())->toBe('cms/templates')
        ->and(TemplateResource::getRelations())->toBe([]);
});

it('configures cms entity resource table without throwing', function (): void {
    $this->actingAs($this->admin);

    $livewire = $this->createStub(HasTable::class);
    $table = Table::make($livewire);
    $table->query(fn () => EntityResource::getModel()::query());

    EntityResource::table($table);

    expect($table->getQuery())->not->toBeNull();
});

it('configures cms resource forms without throwing', function (): void {
    $this->actingAs($this->admin);

    expect(EntityResource::form(Schema::make()))->toBeInstanceOf(Schema::class);
    expect(TemplateResource::form(Schema::make()))->toBeInstanceOf(Schema::class);
    expect(TagResource::form(Schema::make()))->toBeInstanceOf(Schema::class);
    expect(LocationResource::form(Schema::make()))->toBeInstanceOf(Schema::class);
    expect(ContributorResource::form(Schema::make()))->toBeInstanceOf(Schema::class);
});

it('configures cms stats widget as lazy', function (): void {
    $property = new ReflectionProperty(CMSStatsWidget::class, 'isLazy');
    $property->setAccessible(true);

    expect($property->getDeclaringClass()->getName())->toBe(CMSStatsWidget::class)
        ->and($property->getValue())->toBeTrue();
});

it('configures cms tag location and contributor resource tables without throwing', function (): void {
    $this->actingAs($this->admin);

    $resources = [
        TagResource::class,
        LocationResource::class,
        ContributorResource::class,
    ];

    $livewire = $this->createStub(HasTable::class);

    foreach ($resources as $resource_class) {
        $table = Table::make($livewire);
        $table->query(fn () => $resource_class::getModel()::query());
        $resource_class::table($table);

        expect($table->getQuery())->not->toBeNull();
    }
});
