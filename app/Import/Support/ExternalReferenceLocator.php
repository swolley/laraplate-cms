<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Modules\CMS\Models\Category;
use Modules\CMS\Models\Content;
use Modules\CMS\Models\Contributor;
use Modules\CMS\Models\Location;
use Modules\CMS\Models\Tag;
use Modules\CMS\Models\Translations\ContentTranslation;
use Modules\CMS\Models\Translations\ContributorTranslation;
use Modules\CMS\Models\Translations\TagTranslation;
use Modules\Core\Models\RecordOrigin;
use Modules\Core\Models\Translations\TaxonomyTranslation;

/**
 * Resolves and registers import identity/provenance through the generic
 * {@see RecordOrigin} registry (core_record_origins).
 */
final class ExternalReferenceLocator
{
    /**
     * Translation tables used for the deterministic import-slug fallback.
     *
     * @var array<class-string<Model>, array{translation_model: class-string<Model>, foreign_key: string}>
     */
    private const IMPORT_SLUG_TARGETS = [
        Content::class => [
            'translation_model' => ContentTranslation::class,
            'foreign_key' => 'content_id',
        ],
        Category::class => [
            'translation_model' => TaxonomyTranslation::class,
            'foreign_key' => 'taxonomy_id',
        ],
        Contributor::class => [
            'translation_model' => ContributorTranslation::class,
            'foreign_key' => 'contributor_id',
        ],
        Tag::class => [
            'translation_model' => TagTranslation::class,
            'foreign_key' => 'tag_id',
        ],
    ];

    public function __construct(
        private readonly string $locale,
    ) {}

    public function findImportedRecordId(
        Model|string $referable,
        int $external_id,
        string $source_type,
        ?Model $target_model = null,
        ?ImportConnectionContext $context = null,
    ): ?int {
        if ($referable instanceof Model) {
            if ($target_model instanceof Model && $referable::class !== $target_model::class) {
                throw new LogicException(sprintf(
                    'Declared import model [%s] does not match target model [%s].',
                    $referable::class,
                    $target_model::class,
                ));
            }

            $target_model = $referable;
        } else {
            if ($target_model instanceof Model && ! $target_model instanceof $referable) {
                throw new LogicException(sprintf(
                    'Declared import model [%s] does not match target model [%s].',
                    $referable,
                    $target_model::class,
                ));
            }

            $target_model ??= $context?->model($referable) ?? new $referable;
        }

        if ($context instanceof ImportConnectionContext) {
            $context->assertModel($target_model);
        }

        return $this->findByOrigin($target_model, $external_id, $source_type)
            ?? $this->findByImportSlug($target_model, $external_id, $source_type);
    }

    public function hasImportedRecord(
        Model|string $referable,
        int $external_id,
        string $source_type,
        ?Model $target_model = null,
        ?ImportConnectionContext $context = null,
    ): bool {
        return $this->findImportedRecordId(
            $referable,
            $external_id,
            $source_type,
            $target_model,
            $context,
        ) !== null;
    }

    /**
     * Persist (or refresh) the origin of a record in the registry. Keyed by the
     * external identity so repeated imports remain idempotent.
     */
    public function register(
        Model $referable,
        string $source_key,
        ?int $external_id,
        ?string $source_label = null,
        ?string $url = null,
    ): void {
        $now = now();
        $external = $external_id !== null ? (string) $external_id : null;

        $connection = $referable->getConnection();
        $origin_table = (new RecordOrigin)->getTable();

        $query = $connection->table($origin_table)
            ->where('referable_type', $referable->getMorphClass())
            ->where('source_key', $source_key)
            ->when(
                $external !== null,
                fn ($q) => $q->where('external_id', $external),
                fn ($q) => $q->whereNull('external_id')->where('referable_id', $referable->getKey()),
            );

        $existing_id = $query->value('id');

        $values = [
            'referable_id' => $referable->getKey(),
            'source_label' => $source_label,
            'url' => $url,
            'updated_at' => $now,
        ];

        if ($existing_id !== null) {
            $connection->table($origin_table)->where('id', $existing_id)->update($values);

            return;
        }

        $connection->table($origin_table)->insert([
            ...$values,
            'referable_type' => $referable->getMorphClass(),
            'source_key' => $source_key,
            'external_id' => $external,
            'created_at' => $now,
        ]);
    }

    public function importSlug(int $external_id, string $source_type): string
    {
        return 'import-' . preg_replace('/[^a-z0-9_-]+/i', '-', $source_type) . '-' . $external_id;
    }

    private function findByOrigin(Model $referable, int $external_id, string $source_type): ?int
    {
        $id = $this->connectionFor($referable)->table((new RecordOrigin)->getTable())
            ->where('referable_type', $referable->getMorphClass())
            ->where('source_key', $source_type)
            ->where('external_id', (string) $external_id)
            ->value('referable_id');

        return $id !== null ? (int) $id : null;
    }

    private function findByImportSlug(Model $target_model, int $external_id, string $source_type): ?int
    {
        if ($target_model instanceof Location) {
            return null;
        }

        $target = self::IMPORT_SLUG_TARGETS[$target_model::class] ?? null;

        if ($target === null) {
            return null;
        }

        $translation_model = new $target['translation_model'];

        $local_id = $this->connectionFor($target_model)->table($translation_model->getTable())
            ->where('locale', $this->locale)
            ->where('slug', $this->importSlug($external_id, $source_type))
            ->value($target['foreign_key']);

        return $local_id !== null ? (int) $local_id : null;
    }

    private function connectionFor(Model $target_model): ConnectionInterface
    {
        return $target_model->getConnection();
    }
}
