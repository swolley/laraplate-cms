<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Models\Content;
use Modules\CMS\Tests\TestCase;
use Modules\Core\Approvals\Operation;
use Modules\Core\Models\ModelEmbedding;
use Modules\Core\Models\Modification;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The embeddings and the modifications of a record hang from it by a polymorphic key, with no foreign key to
 * take them along: a hard delete must remove them, a move to the bin must not.
 */
beforeEach(function (): void {
    setupCMSEntities();
    $this->content = Content::factory()->create();
    $this->content->embeddings()->create(['embedding' => [0.1, 0.2], 'locale' => 'en', 'model_key' => 'test-model']);
    $this->content->modifications()->save(new Modification([
        'active' => true,
        'operation' => Operation::Update,
        'approvers_required' => 1,
        'disapprovers_required' => 1,
        'md5' => md5('pending'),
        'modifications' => ['title' => ['original' => 'a', 'modified' => 'b']],
    ]));
});

function children_of(Content $content): array
{
    return [
        ModelEmbedding::query()->where('model_type', $content->getMorphClass())->where('model_id', $content->getKey())->count(),
        Modification::query()->where('modifiable_type', $content->getMorphClass())->where('modifiable_id', $content->getKey())->count(),
    ];
}

it('removes the embeddings and the modifications of a record deleted for good', function (): void {
    $this->content->forceDelete();

    expect(children_of($this->content))->toBe([0, 0]);
});

it('keeps them when the record only goes to the bin', function (): void {
    $this->content->delete();

    expect(children_of($this->content))->toBe([1, 1]);
});
