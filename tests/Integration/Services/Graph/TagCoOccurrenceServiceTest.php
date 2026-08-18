<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CMS\Casts\EntityType;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Tag;
use Modules\CMS\Services\Graph\TagCoOccurrenceService;
use Modules\CMS\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    setupCMSEntities([EntityType::Contents]);
});

function graph_tag(string $name): Tag
{
    $tag = Tag::factory()->create();
    $tag->translations()->where('locale', 'en')->update(['name' => $name]);

    return $tag;
}

function graph_content(): Content
{
    return Content::factory()->create(['valid_from' => now()->subDay(), 'valid_to' => null]);
}

it('builds nodes weighted by usage and edges weighted by co-occurrence', function (): void {
    $cinema = graph_tag('Cinema');
    $drama = graph_tag('Drama');
    $news = graph_tag('News');

    // c1: cinema+drama, c2: cinema+drama, c3: cinema+news
    $c1 = graph_content();
    $c2 = graph_content();
    $c3 = graph_content();
    $c1->tags()->sync([$cinema->id, $drama->id]);
    $c2->tags()->sync([$cinema->id, $drama->id]);
    $c3->tags()->sync([$cinema->id, $news->id]);

    $graph = app(TagCoOccurrenceService::class)->graph();

    $nodes = collect($graph['nodes'])->keyBy('id');
    expect($nodes[$cinema->id]['weight'])->toBe(3)
        ->and($nodes[$cinema->id]['label'])->toBe('Cinema')
        ->and($nodes[$drama->id]['weight'])->toBe(2)
        ->and($nodes[$news->id]['weight'])->toBe(1);

    // Edges are undirected (source < target); cinema-drama share 2 contents, cinema-news 1.
    $edge = static function (array $graph, int $a, int $b): ?array {
        [$lo, $hi] = $a < $b ? [$a, $b] : [$b, $a];

        return collect($graph['edges'])->first(fn (array $e): bool => $e['source'] === $lo && $e['target'] === $hi);
    };

    expect($edge($graph, $cinema->id, $drama->id)['weight'])->toBe(2)
        ->and($edge($graph, $cinema->id, $news->id)['weight'])->toBe(1)
        ->and($edge($graph, $drama->id, $news->id))->toBeNull();
});

it('drops edges below the minimum co-occurrence threshold', function (): void {
    $a = graph_tag('Alpha');
    $b = graph_tag('Beta');

    // Alpha+Beta co-occur on exactly one content.
    graph_content()->tags()->sync([$a->id, $b->id]);

    $graph = app(TagCoOccurrenceService::class)->graph(minCoOccurrence: 2);

    expect($graph['nodes'])->toHaveCount(2)
        ->and($graph['edges'])->toHaveCount(0);
});

it('returns an empty graph when there are no tagged contents', function (): void {
    graph_tag('Orphan');

    $graph = app(TagCoOccurrenceService::class)->graph();

    expect($graph['nodes'])->toBe([])
        ->and($graph['edges'])->toBe([]);
});
