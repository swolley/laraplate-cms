<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\ContentReference;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class);

it('content reference model has correct structure', function (): void {
    $reflection = new ReflectionClass(ContentReference::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->toContain('protected $fillable')
        ->and($source)->toContain('protected $hidden')
        ->and($source)->toContain(CMSTables::ContentsReferences->value);
});

it('content reference model uses sortable and soft delete traits', function (): void {
    $traits = array_values(class_uses_recursive(ContentReference::class));

    expect($traits)->toContain(Modules\Core\Models\Concerns\SortableTrait::class)
        ->and($traits)->toContain(Modules\Core\SoftDeletes\SoftDeletes::class);
});

it('scopes build sort query by content id', function (): void {
    $reference = new ContentReference(['content_id' => 42]);
    $query = $reference->buildSortQuery();

    expect($query)->toBeInstanceOf(Builder::class);

    $sql = $query->toSql();
    expect($sql)->toContain('content_id');
});
