<?php

declare(strict_types=1);

use Modules\CMS\Import\Support\ImportIdMap;

it('remembers and resolves external to local ids', function (): void {
    $map = new ImportIdMap();

    $map->remember('contents', 42, 7);

    expect($map->resolve('contents', 42))->toBe(7)
        ->and($map->resolve('contents', 99))->toBeNull()
        ->and($map->resolveMany('contents', [42, 99]))->toBe([7]);
});
