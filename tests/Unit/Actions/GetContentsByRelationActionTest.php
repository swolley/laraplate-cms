<?php

declare(strict_types=1);

use Modules\Cms\Actions\Contents\GetContentsByRelationAction;
use Modules\Core\Http\Requests\ListRequest;
use Tests\TestCase;

uses(TestCase::class);

it('builds filters', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $action = new GetContentsByRelationAction();

    $result = $action($request, 'tags', 'value', 'articles');

    expect($result['entity'])->toBe('contents');
    expect($result['filters'])->not->toBeEmpty();
});
