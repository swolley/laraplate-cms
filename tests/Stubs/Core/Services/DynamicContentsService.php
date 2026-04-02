<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Models\Entity as EntityModel;
use Modules\Core\Contracts\IDynamicEntityTypable;
use Modules\Core\Models\Pivot\Presettable;
use Modules\Core\Models\Preset;
use ReflectionClass;

/**
 * Test stub: same as Core {@see DynamicContentsService} but uses the concrete CMS {@see EntityModel} for queries and cache keys (Core Entity is abstract in standalone CMS tests).
 */
final class DynamicContentsService
{
    private static ?self $instance = null;

    /**
     * @var Collection<int, EntityModel>|null
     */
    private ?Collection $entities_cache = null;

    /**
     * @var Collection<int, Preset>|null
     */
    private ?Collection $presets_cache = null;

    /**
     * @var Collection<int, Presettable>|null
     */
    private ?Collection $presettables_cache = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * @return Collection<int, EntityModel>
     */
    public function fetchAvailableEntities(IDynamicEntityTypable $type): Collection
    {
        if ($this->entities_cache instanceof Collection) {
            return $this->entities_cache->where('type', $type);
        }

        $entity_model = new EntityModel();
        $cache_key = $entity_model->getCacheKey();

        $this->entities_cache = Cache::memo()->rememberForever(
            $cache_key,
            static fn (): Collection => EntityModel::query()
                ->withoutGlobalScopes()
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get(),
        );

        return $this->entities_cache->where('type', $type);
    }

    /**
     * @return Collection<int, Preset>
     */
    public function fetchAvailablePresets(IDynamicEntityTypable $type): Collection
    {
        if ($this->presets_cache instanceof Collection) {
            return $this->presets_cache->filter(fn (Preset $preset): bool => $preset->entity?->type === $type);
        }

        $preset_model = new Preset();
        $cache_key = $preset_model->getCacheKey();

        $this->presets_cache = Cache::memo()->rememberForever(
            $cache_key,
            static fn (): Collection => Preset::query()
                ->withoutGlobalScopes()
                ->with(['fields', 'entity'])
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get(),
        );

        return $this->presets_cache->filter(fn (Preset $preset): bool => $preset->entity?->type === $type);
    }

    /**
     * @return Collection<int, Presettable>
     */
    public function fetchAvailablePresettables(IDynamicEntityTypable $type): Collection
    {
        if ($this->presettables_cache instanceof Collection) {
            return $this->presettables_cache->filter(fn (Presettable $presettable): bool => $presettable->entity?->type === $type);
        }

        $cache_key = new ReflectionClass(Presettable::class)->newInstanceWithoutConstructor()->getTable();

        $this->presettables_cache = Cache::memo()->rememberForever(
            $cache_key,
            static fn (): Collection => Presettable::query()
                ->join('presets', 'presettables.preset_id', '=', 'presets.id')
                ->join('entities', 'presets.entity_id', '=', 'entities.id')
                ->whereNull('presettables.deleted_at')
                ->whereNull('presets.deleted_at')
                ->addSelect('presettables.*', DB::raw('CASE WHEN presets.is_default THEN 1 ELSE 0 END + CASE WHEN entities.is_default THEN 1 ELSE 0 END as order_score'))
                ->orderBy('order_score', 'desc')
                ->get(),
        );

        return $this->presettables_cache->filter(fn (Presettable $presettable): bool => $presettable->entity?->type === $type);
    }

    public function clearEntitiesCache(): void
    {
        $this->entities_cache = null;
    }

    public function clearPresetsCache(): void
    {
        $this->presets_cache = null;
    }

    public function clearPresettablesCache(): void
    {
        $this->presettables_cache = null;
    }

    public function clearAllCaches(): void
    {
        $this->entities_cache = null;
        $this->presets_cache = null;
        $this->presettables_cache = null;
    }
}
