<?php

declare(strict_types=1);

use Modules\Cms\Casts\ReadingStatistics;

it('returns zeros for empty block list', function (): void {
    $stats = ReadingStatistics::fromBlocks([]);

    expect($stats->characters)->toBe(0)
        ->and($stats->words)->toBe(0)
        ->and($stats->sentences)->toBe(0)
        ->and($stats->paragraphs)->toBe(0)
        ->and($stats->reading_time)->toBe(0.0);
});

it('counts words and characters from paragraph html', function (): void {
    $blocks = [
        [
            'type' => 'paragraph',
            'data' => ['text' => '<p>Hello <strong>world</strong> today.</p>'],
        ],
    ];

    $stats = ReadingStatistics::fromBlocks($blocks);

    expect($stats->words)->toBe(3)
        ->and($stats->paragraphs)->toBe(1)
        ->and($stats->sentences)->toBeGreaterThanOrEqual(1)
        ->and($stats->characters)->toBeGreaterThan(0)
        ->and($stats->reading_time)->toBeGreaterThan(0.0);
});

it('aggregates list items and captions', function (): void {
    $blocks = [
        [
            'type' => 'list',
            'data' => [
                'items' => ['First item', 'Second item'],
            ],
        ],
        [
            'type' => 'image',
            'data' => [
                'caption' => 'Photo credit line',
            ],
        ],
    ];

    $stats = ReadingStatistics::fromBlocks($blocks);

    expect($stats->words)->toBeGreaterThanOrEqual(5)
        ->and($stats->paragraphs)->toBe(2);
});

it('counts words inside table cells', function (): void {
    $blocks = [
        [
            'type' => 'table',
            'data' => [
                'content' => [
                    ['One', 'Two three'],
                ],
            ],
        ],
    ];

    $stats = ReadingStatistics::fromBlocks($blocks);

    expect($stats->words)->toBe(3);
});

it('strips editor find marks before measuring', function (): void {
    $html = '<span data-editor-find-mark="1">visible</span> text';
    $stripped = ReadingStatistics::stripFindMarksFromHtmlString($html);

    expect($stripped)->toContain('visible')
        ->and($stripped)->toContain('text');
});

it('accepts stdClass blocks from json decode', function (): void {
    $payload = json_decode((string) json_encode([
        'blocks' => [
            (object) [
                'type' => 'paragraph',
                'data' => (object) ['text' => 'One two.'],
            ],
        ],
    ]));

    $stats = ReadingStatistics::fromBlocks($payload->blocks);

    expect($stats->words)->toBe(2);
});
