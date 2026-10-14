<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\CMS\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\CMS\Filament\Resources\Categories\Pages\EditCategory;
use Modules\CMS\Filament\Resources\Tags\Pages\CreateTag;
use Modules\CMS\Filament\Resources\Tags\Pages\EditTag;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Tag;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Tests\Support\HttpContext;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    setupCMSEntities();
});

it('puts a new tag last whatever position it is given', function (): void {
    $first = Tag::factory()->create();

    $tag = Tag::factory()->create(['order_column' => $first->order_column + 40]);

    expect($tag->fresh()->order_column)->toBe($first->fresh()->order_column + 1);
});

it('puts a new category last whatever position it is given', function (): void {
    $first = Category::factory()->create();

    $category = Category::factory()->create(['order_column' => $first->order_column + 40]);

    expect($category->fresh()->order_column)->toBe($first->fresh()->order_column + 1);
});

it('does not offer the position when creating', function (string $page, string $model): void {
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'insert']);

    Livewire::test($page)->assertFormFieldHidden('order_column');
})->with([
    'a tag' => [CreateTag::class, Tag::class],
    'a category' => [CreateCategory::class, Category::class],
]);

it('offers the position when editing', function (string $page, string $model): void {
    $record = $model::factory()->create();
    HttpContext::panelActorWithoutApproval(new $model, ['select', 'update']);

    Livewire::test($page, ['record' => $record->getKey()])->assertFormFieldVisible('order_column');
})->with([
    'a tag' => [EditTag::class, Tag::class],
    'a category' => [EditCategory::class, Category::class],
]);
