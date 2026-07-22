<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Modules\CMS\Models\Contributor;

/**
 * Ensures the canonical default contributor exists for import fallbacks.
 *
 * Unlike {@see ContributorUpserter}, this never writes to core_record_origins:
 * the default editorial identity is local infrastructure, not an external source
 * record.
 */
final class DefaultContributorProvisioner
{
    /**
     * @var array<string, int>
     */
    private array $cached_ids = [];

    public function __construct(
        private readonly ImportPresetProvisioner $preset_provisioner,
        private readonly EntityPresetResolver $entity_preset_resolver,
        private readonly ContributorMatcher $contributor_matcher,
        private readonly string $locale,
    ) {}

    public function ensure(?ImportConnectionContext $context = null): int
    {
        $context ??= new ImportConnectionContext(new Contributor);
        $contributor_model = $context->model(Contributor::class);

        $connection_name = $context->connectionName();

        if (isset($this->cached_ids[$connection_name])) {
            return $this->cached_ids[$connection_name];
        }

        $config = $this->config();
        $binding = $this->contributorBinding();

        $existing = $this->contributor_matcher->findExisting(
            slug: $config['slug'],
            name: $config['name'],
            context: $context,
        );

        if ($existing !== null) {
            return $this->cached_ids[$connection_name] = $existing;
        }

        $this->preset_provisioner->ensurePreset($binding['entity'], $binding['preset'], $context);

        $contributor = $contributor_model->newInstance([
            'entity_id' => $this->entity_preset_resolver->entityId($binding['entity'], $context),
            'presettable_id' => $this->entity_preset_resolver->presettableId($binding['entity'], $binding['preset'], $context),
            'name' => $config['name'],
        ]);
        $contributor->save();

        if ($config['slug'] !== '') {
            $contributor->setTranslation($this->locale, [
                'slug' => $config['slug'],
                'components' => [],
            ]);
            $contributor->save();
        }

        return $this->cached_ids[$connection_name] = (int) $contributor->id;
    }

    public function reset(): void
    {
        $this->cached_ids = [];
    }

    /**
     * @return array{name: string, slug: string}
     */
    private function config(): array
    {
        /** @var array{name?: string, slug?: string} $config */
        $config = config('cms.import.default_contributor', []);

        return [
            'name' => (string) ($config['name'] ?? 'Redazione'),
            'slug' => (string) ($config['slug'] ?? 'redazione'),
        ];
    }

    /**
     * @return array{entity: string, preset: string}
     */
    private function contributorBinding(): array
    {
        /** @var array{entity?: string, preset?: string} $binding */
        $binding = config('cms.import.bindings.contributors.contributor', []);

        return [
            'entity' => ImportEntityNames::normalize((string) ($binding['entity'] ?? ImportEntityNames::CONTRIBUTORS)),
            'preset' => (string) ($binding['preset'] ?? 'default'),
        ];
    }
}
