<?php

declare(strict_types=1);

use Modules\Cms\Actions\Contents\GetContentsByRelationAction;
use Modules\Core\Http\Requests\ListRequest;
use Tests\TestCase;

final class GetContentsByRelationActionTest extends TestCase
{
    public function test_builds_filters(): void
    {
        $request = new class extends ListRequest
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'filters' ? [] : $default;
            }
        };

        $action = new GetContentsByRelationAction();

        $result = $action($request, 'tags', 'value', 'articles');

        $this->assertSame('contents', $result['entity']);
        $this->assertNotEmpty($result['filters']);
    }
}

