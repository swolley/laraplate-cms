<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Tables\Contracts\HasTable as HasTableContract;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Content;
use Modules\Cms\Models\Entity;
use Modules\Cms\Tests\TestCase;
use Modules\Cms\Tests\Unit\Filament\Utils\CmsHasTableTraitHarness;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCmsEntities([EntityType::CONTENTS]);
});

it('adds entity and preset groups when the table model uses dynamic contents', function (): void {
    $livewire = Mockery::mock(HasTableContract::class)->shouldIgnoreMissing();
    $table = Table::make($livewire)->query(Content::query());

    $configure = new ReflectionMethod(CmsHasTableTraitHarness::class, 'configureTable');
    $configure->setAccessible(true);
    $configured = $configure->invoke(null, $table, null, null, [], null);

    $groupsProperty = new ReflectionProperty(Table::class, 'groups');
    $groupsProperty->setAccessible(true);
    $groups = $groupsProperty->getValue($configured);

    expect($groups)->toBeArray()->toHaveCount(2);
});

it('pushes preset-related columns for dynamic content models', function (): void {
    $livewire = Mockery::mock(HasTableContract::class)->shouldIgnoreMissing();
    $table = Table::make($livewire)->query(Content::query());
    $modelInstance = (new ReflectionClass(Content::class))->newInstanceWithoutConstructor();

    $configureColumns = new ReflectionMethod(CmsHasTableTraitHarness::class, 'configureColumns');
    $configureColumns->setAccessible(true);
    $configureColumns->invoke(
        null,
        $table,
        false,
        false,
        false,
        false,
        false,
        false,
        null,
        $modelInstance,
    );

    $columnNames = array_map(
        static fn ($col) => $col->getName(),
        $table->getColumns(),
    );

    expect($columnNames)->toContain('presettable.entity.name', 'presettable.preset.name');
});

it('pushes preset filter and resolves preset select options for the entity type', function (): void {
    $livewire = Mockery::mock(HasTableContract::class)->shouldIgnoreMissing();
    $table = Table::make($livewire)->query(Content::query());
    $modelInstance = (new ReflectionClass(Content::class))->newInstanceWithoutConstructor();
    $user = new User([
        'name' => 'Table test',
        'email' => 'table-test-' . uniqid('', true) . '@example.test',
        'password' => bcrypt('password'),
    ]);
    $user->save();

    $configureFilters = new ReflectionMethod(CmsHasTableTraitHarness::class, 'configureFilters');
    $configureFilters->setAccessible(true);
    $configureFilters->invoke(
        null,
        $table,
        false,
        false,
        false,
        false,
        false,
        null,
        $modelInstance,
        'sqlite.contents',
        $user,
    );

    $filterNames = array_map(
        static fn ($f) => $f->getName(),
        $table->getFilters(),
    );

    expect($filterNames)->toContain('preset');

    $optionsMethod = new ReflectionMethod(CmsHasTableTraitHarness::class, 'presetSelectFilterOptions');
    $optionsMethod->setAccessible(true);
    $options = $optionsMethod->invoke(null, EntityType::CONTENTS);

    expect($options)->toBeArray()->not->toBeEmpty();
});

it('does not add dynamic-content groups for models without HasDynamicContents', function (): void {
    $livewire = Mockery::mock(HasTableContract::class)->shouldIgnoreMissing();
    $table = Table::make($livewire)->query(Entity::query());

    $configure = new ReflectionMethod(CmsHasTableTraitHarness::class, 'configureTable');
    $configure->setAccessible(true);
    $configured = $configure->invoke(null, $table, null, null, [], null);

    $groupsProperty = new ReflectionProperty(Table::class, 'groups');
    $groupsProperty->setAccessible(true);
    $groups = $groupsProperty->getValue($configured);

    expect($groups)->toBeArray()->toBeEmpty();
});

it('reports false for hasDynamicContents on models without the trait', function (): void {
    $modelInstance = (new ReflectionClass(Entity::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(CmsHasTableTraitHarness::class, 'hasDynamicContents');
    $method->setAccessible(true);

    expect($method->invoke(null, $modelInstance))->toBeFalse();
});

it('reports true for hasDynamicContents on Content', function (): void {
    $modelInstance = (new ReflectionClass(Content::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(CmsHasTableTraitHarness::class, 'hasDynamicContents');
    $method->setAccessible(true);

    expect($method->invoke(null, $modelInstance))->toBeTrue();
});
