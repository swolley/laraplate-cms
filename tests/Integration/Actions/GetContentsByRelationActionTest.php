<?php

declare(strict_types=1);

use Modules\CMS\Actions\Contents\GetContentsByRelationAction;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Http\Requests\ListRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

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

it('normalizes singular relation name when content model uses plural method', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $action = new GetContentsByRelationAction();

    $result = $action($request, 'category', 'acme', 'contents');

    expect($result['entity'])->toBe('contents');
    expect($result['filters'])->not->toBeEmpty();
});

it('keeps only array filters from the request filters payload', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [
                ['property' => 'contents.title', 'value' => 'Hello', 'operator' => '='],
                'ignored',
            ] : $default;
        }
    };

    $action = new GetContentsByRelationAction();

    $result = $action($request, 'tags', 'value', 'contents');

    expect($result['filters'][0]['filters'][0])->toBe([
        'property' => 'contents.title',
        'value' => 'Hello',
        'operator' => '=',
    ]);
});

it('rejects unknown relations after normalization', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $action = new GetContentsByRelationAction();

    expect(fn () => $action($request, 'not_a_real_relation_xyz', 'v', 'contents'))
        ->toThrow(BadRequestException::class, 'Invalid relation');
});
