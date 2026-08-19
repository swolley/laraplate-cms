<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Actions\Contents\GetContentsByRelationAction;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Http\Requests\ListRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $content = new Content;

    if (! $content->getConnection()->getSchemaBuilder()->hasColumn($content->getTable(), 'shared_components')) {
        $this->markTestSkipped('GetContentsByRelation action requires full Core runtime.');
    }

    setupCMSEntities();
});

it('builds filters', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $result = new GetContentsByRelationAction()($request, 'tags', 'value', 'articles');

    expect($result['entity'])->toBe('contents');
    expect($result['filters'])->not->toBeEmpty();
});

it('resolves the related model id for the value and emits an id-in relation filter', function (): void {
    $category = Category::factory()->create();
    $category->name = 'Acme';
    $category->slug = 'acme';
    $category->save();

    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $result = new GetContentsByRelationAction()($request, 'categories', 'acme', 'contents');

    $relationGroup = end($result['filters'][0]['filters']);
    $relationFilter = $relationGroup['filters'][0];

    expect($relationFilter['property'])->toBe('categories.id');
    expect($relationFilter['operator'])->toBe('in');
    expect($relationFilter['value'])->toBe([$category->id]);
});

it('emits an empty id set when the value matches no related model', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $result = new GetContentsByRelationAction()($request, 'categories', 'does-not-exist', 'contents');

    $relationGroup = end($result['filters'][0]['filters']);
    $relationFilter = $relationGroup['filters'][0];

    expect($relationFilter['property'])->toBe('categories.id');
    expect($relationFilter['value'])->toBe([]);
});

it('normalizes singular relation name when content model uses plural method', function (): void {
    $request = new class extends ListRequest
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'filters' ? [] : $default;
        }
    };

    $result = new GetContentsByRelationAction()($request, 'category', 'acme', 'contents');

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

    $result = new GetContentsByRelationAction()($request, 'tags', 'value', 'contents');

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

    expect(fn () => new GetContentsByRelationAction()($request, 'not_a_real_relation_xyz', 'v', 'contents'))
        ->toThrow(BadRequestException::class, 'Invalid relation');
});
