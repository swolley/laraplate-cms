<?php

declare(strict_types=1);

it('CMS RAG MODULE.md includes mermaid diagrams for core flows', function (): void {
    $path = dirname(__DIR__, 2) . '/docs/rag/MODULE.md';

    expect(file_exists($path))->toBeTrue();

    $content = (string) file_get_contents($path);

    expect(substr_count($content, '```mermaid'))->toBeGreaterThanOrEqual(8)
        ->and(substr_count($content, '```mermaid'))->toEqual(substr_count($content, "```\n"))
        ->and($content)->toContain('### Module boundaries')
        ->and($content)->toContain('### Dynamic content model')
        ->and($content)->toContain('Content relationships and morph pivots')
        ->and($content)->toContain('### Multilocale translations')
        ->and($content)->toContain('### Multimedia pipeline')
        ->and($content)->toContain('### Location and geocoding')
        ->and($content)->toContain('### Tagging and taxonomy')
        ->and($content)->toContain('### Lifecycle: validity, approvals, locking')
        ->and($content)->toContain('### Search indexing')
        ->and($content)->toContain('ContentObserver')
        ->and($content)->toContain('Presettable')
        ->and($content)->toContain('HasMultimedia')
        ->and($content)->toContain('GeocodeLocationAction')
        ->and($content)->toContain('NominatimService')
        ->and($content)->toContain('CMSPlugin');
});
