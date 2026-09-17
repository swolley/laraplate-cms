<?php

declare(strict_types=1);

it('CMS RAG MODULE.md includes mermaid diagrams for core flows', function (): void {
    $path = dirname(__DIR__, 2) . '/docs/rag/MODULE.md';

    expect(file_exists($path))->toBeTrue();

    $content = (string) file_get_contents($path);

    // Every fenced block must close, and the document is not only mermaid: it also
    // carries a shell block. Comparing the mermaid count against the closing fences
    // broke the moment that block arrived, while the fences were balanced.
    $opening_fences = preg_match_all('/^```[a-z]+$/m', $content);
    $closing_fences = preg_match_all('/^```$/m', $content);

    expect(mb_substr_count($content, '```mermaid'))->toBeGreaterThanOrEqual(8)
        ->and($opening_fences)->toEqual($closing_fences)
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
