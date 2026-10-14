<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\CMS\Filament\Resources\Contributors\Pages\CreateContributor;
use Modules\CMS\Filament\Resources\Locations\Pages\CreateLocation;
use Modules\CMS\Filament\Resources\Presets\Pages\CreatePreset;
use Modules\CMS\Filament\Resources\Tags\Pages\CreateTag;
use Modules\CMS\Filament\Resources\Templates\Pages\CreateTemplate;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Preset;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Models\Template;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities();
});

it('does not offer the soft-delete flag, which follows deleted_at', function (string $page, string $model): void {
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'insert']);

    Livewire::test($page)->assertFormFieldDoesNotExist('is_deleted');
})->with([
    'a tag' => [CreateTag::class, Tag::class],
    'a category' => [CreateCategory::class, Category::class],
    'a preset' => [CreatePreset::class, Preset::class],
    'a contributor' => [CreateContributor::class, Contributor::class],
    'a template' => [CreateTemplate::class, Template::class],
    'a location' => [CreateLocation::class, Location::class],
]);
