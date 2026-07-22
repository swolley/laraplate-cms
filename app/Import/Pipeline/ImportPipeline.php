<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Pipeline;

use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Import\Dto\ImportGraphDto;
use Modules\CMS\Import\Support\CategoryHierarchySorter;
use Modules\CMS\Import\Support\ContributorDefaults;
use Modules\CMS\Import\Support\ImportConnectionContext;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Support\ImportPresetProvisioner;
use Modules\CMS\Import\Upserters\CategoryUpserter;
use Modules\CMS\Import\Upserters\ContentUpserter;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Import\Upserters\LocationUpserter;
use Modules\CMS\Import\Upserters\TagUpserter;
use Modules\CMS\Models\Content;

final class ImportPipeline
{
    public function __construct(
        private readonly ImportPresetProvisioner $preset_provisioner,
        private readonly CategoryHierarchySorter $category_sorter,
        private readonly CategoryUpserter $category_upserter,
        private readonly ContributorUpserter $contributor_upserter,
        private readonly TagUpserter $tag_upserter,
        private readonly LocationUpserter $location_upserter,
        private readonly ContentUpserter $content_upserter,
        private readonly ContributorDefaults $contributor_defaults,
        private readonly ImportIdMap $id_map,
    ) {}

    public function import(ImportGraphDto $graph, ?Model $root_model = null): int
    {
        $root_model ??= new Content;
        $context = new ImportConnectionContext($root_model);

        $context->preflight($this->participantModelClasses($graph));

        return $context->connection()->transaction(
            fn (): int => $this->importGraph($graph, $context),
        );
    }

    public function resetState(): void
    {
        $this->id_map->reset();
        $this->contributor_defaults->reset();
    }

    private function importGraph(ImportGraphDto $graph, ImportConnectionContext $context): int
    {
        $this->preset_provisioner->provisionFromGraph($graph, $context);

        foreach ($this->category_sorter->sort($graph->categories) as $category) {
            $this->category_upserter->upsert($category, $context);
        }

        foreach ($graph->contributors as $contributor) {
            $this->contributor_upserter->upsert($contributor, $context);
        }

        foreach ($graph->tags as $tag) {
            $this->tag_upserter->upsert($tag, $context);
        }

        $location_ids = [];

        foreach ($graph->locations as $location) {
            $location_ids[] = $this->location_upserter->upsert($location, $context);
        }

        $category_ids = $this->id_map->resolveMany(
            'categories',
            $graph->content->categoryExternalIds,
            $context->connectionName(),
            $graph->content->sourceType,
        );
        $contributor_ids = $this->id_map->resolveMany(
            'contributors',
            $graph->content->contributorExternalIds,
            $context->connectionName(),
            $graph->content->sourceType,
        );

        if ($contributor_ids === []) {
            $contributor_ids = [$this->contributor_defaults->resolveContributorId($context)];
        }

        $tag_ids = $this->id_map->resolveMany(
            'tags',
            $graph->content->tagExternalIds,
            $context->connectionName(),
            $graph->content->sourceType,
        );

        foreach ($graph->relatedGraphs as $related_graph) {
            $this->importGraph($related_graph, $context);
        }

        return $this->content_upserter->upsert(
            $graph->content,
            $category_ids,
            $contributor_ids,
            $tag_ids,
            $location_ids,
            $context,
        );
    }

    /**
     * @return list<class-string<Model>>
     */
    private function participantModelClasses(ImportGraphDto $graph): array
    {
        $classes = [
            ...$this->preset_provisioner->participantModelClasses(),
            ...$this->content_upserter->participantModelClasses(),
            ...$this->contributor_upserter->participantModelClasses(),
            ...($graph->categories === [] ? [] : $this->category_upserter->participantModelClasses()),
            ...($graph->contributors === [] ? [] : $this->contributor_upserter->participantModelClasses()),
            ...($graph->tags === [] ? [] : $this->tag_upserter->participantModelClasses()),
            ...($graph->locations === [] ? [] : $this->location_upserter->participantModelClasses()),
        ];

        foreach ($graph->relatedGraphs as $related_graph) {
            $classes = [...$classes, ...$this->participantModelClasses($related_graph)];
        }

        return array_values(array_unique($classes));
    }
}
