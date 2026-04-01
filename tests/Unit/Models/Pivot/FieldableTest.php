<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cms\Models\Pivot\Fieldable;
use Modules\Cms\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('applies ordered scope on order column ascending', function (): void {
    $sql = Fieldable::query()->ordered()->toSql();

    expect($sql)->toContain('order_column')
        ->and($sql)->toContain('asc');
});
