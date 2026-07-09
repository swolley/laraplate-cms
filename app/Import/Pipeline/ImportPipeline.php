<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Pipeline;

use Illuminate\Support\Facades\DB;
use Modules\CMS\Import\Dto\ImportGraphDto;
use Modules\CMS\Import\Support\CategoryHierarchySorter;
use Modules\CMS\Import\Support\ContributorDefaults;
use Modules\CMS\Import\Support\ImportIdMap;
use Modules\CMS\Import\Support\ImportPresetProvisioner;
use Modules\CMS\Import\Upserters\CategoryUpserter;
use Modules\CMS\Import\Upserters\ContentUpserter;
use Modules\CMS\Import\Upserters\ContributorUpserter;
use Modules\CMS\Import\Upserters\LocationUpserter;
use Modules\CMS\Import\Upserters\TagUpserter;

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

    public function import(ImportGraphDto $graph): int
    {
        return DB::transaction(function () use ($graph): int {
            $this->preset_provisioner->provisionFromGraph($graph);

            foreach ($this->category_sorter->sort($graph->categories) as $category) {
                $this->category_upserter->upsert($category);
            }

            foreach ($graph->contributors as $contributor) {
                $this->contributor_upserter->upsert($contributor);
            }

            foreach ($graph->tags as $tag) {
                $this->tag_upserter->upsert($tag);
            }

            $location_ids = [];

            foreach ($graph->locations as $location) {
                $location_ids[] = $this->location_upserter->upsert($location);
            }

            $category_ids = $this->id_map->resolveMany(
                'categories',
                $graph->content->categoryExternalIds,
            );
            $contributor_ids = $this->id_map->resolveMany(
                'contributors',
                $graph->content->contributorExternalIds,
            );

            if ($contributor_ids === []) {
                $contributor_ids = [$this->contributor_defaults->resolveContributorId()];
            }

            $tag_ids = $this->id_map->resolveMany('tags', $graph->content->tagExternalIds);

            foreach ($graph->relatedGraphs as $related_graph) {
                $this->import($related_graph);
            }

            $content_id = $this->content_upserter->upsert(
                $graph->content,
                $category_ids,
                $contributor_ids,
                $tag_ids,
                $location_ids,
            );

            return $content_id;
        });
    }
}
