<?php

declare(strict_types=1);

namespace Modules\Cms\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Casts\EntityType;
use Modules\Cms\Models\Entity;
use Modules\Cms\Models\Pivot\Presettable;
use Modules\Cms\Models\Preset;
use ReflectionClass;

/**
 * Singleton service that caches dynamic contents data in-memory during the request/command scope.
 * This prevents redundant cache access when the same data is requested multiple times.
 */
final class DynamicContentsService
{
    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

    /**
     * In-memory cache for entities (all types).
     */
    private ?Collection $entities_cache = null;

    /**
     * In-memory cache for presets (all types).
     */
    private ?Collection $presets_cache = null;

    /**
     * In-memory cache for presettables (all types).
     */
    private ?Collection $presettables_cache = null;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct() {}

    /**
     * Get service instance (singleton pattern).
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Reset the singleton instance (useful for testing or cache invalidation).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Fetch available entities for a given type.
     * Uses in-memory cache first, then external cache, then database.
     *
     * @return Collection<Entity>
     */
    public function fetchAvailableEntities(EntityType $type): Collection
    {
        // Check in-memory cache first
        if ($this->entities_cache !== null) {
            return $this->entities_cache->where('type', $type);
        }

        // Load from external cache or database, then store in memory
        $entity_model = new Entity();
        $cache_key = $entity_model->getCacheKey();

        $this->entities_cache = Cache::memo()->rememberForever(
            $cache_key,
            fn (): Collection => Entity::query()
                ->withoutGlobalScopes()
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get(),
        );

        return $this->entities_cache->where('type', $type);
    }

    /**
     * Fetch available presets for a given type.
     * Uses in-memory cache first, then external cache, then database.
     *
     * @return Collection<Preset>
     */
    public function fetchAvailablePresets(EntityType $type): Collection
    {
        // Check in-memory cache first
        if ($this->presets_cache !== null) {
            return $this->presets_cache->filter(fn (Preset $preset): bool => $preset->entity?->type === $type);
        }

        // Load from external cache or database, then store in memory
        $preset_model = new Preset();
        $cache_key = $preset_model->getCacheKey();

        $this->presets_cache = Cache::memo()->rememberForever(
            $cache_key,
            fn (): Collection => Preset::query()
                ->withoutGlobalScopes()
                ->with(['fields', 'entity'])
                ->orderBy('is_default', 'desc')
                ->orderBy('name', 'asc')
                ->get(),
        );

        return $this->presets_cache->filter(fn (Preset $preset): bool => $preset->entity?->type === $type);
    }

    /**
     * Fetch available presettables for a given type.
     * Uses in-memory cache first, then external cache, then database.
     *
     * @return Collection<Presettable>
     */
    public function fetchAvailablePresettables(EntityType $type): Collection
    {
        // Check in-memory cache first
        if ($this->presettables_cache !== null) {
            return $this->presettables_cache->filter(fn (Presettable $presettable): bool => $presettable->entity?->type === $type);
        }

        // Load from external cache or database, then store in memory
        // Use class name to get table name without instantiating model (avoids database access during boot)
        $cache_key = (new ReflectionClass(Presettable::class))->newInstanceWithoutConstructor()->getTable();

        $this->presettables_cache = Cache::memo()->rememberForever(
            $cache_key,
            fn (): Collection => Presettable::query()
                ->withoutGlobalScopes()
                ->join('presets', 'presettables.preset_id', '=', 'presets.id')
                ->join('entities', 'presets.entity_id', '=', 'entities.id')
                ->addSelect('presettables.*', DB::raw('CASE WHEN presets.is_default THEN 1 ELSE 0 END + CASE WHEN entities.is_default THEN 1 ELSE 0 END as order_score'))
                ->orderBy('order_score', 'desc')
                ->get(),
        );

        return $this->presettables_cache->filter(fn (Presettable $presettable): bool => $presettable->entity?->type === $type);
    }

    /**
     * Clear in-memory cache for entities.
     * Should be called when entities are modified.
     */
    public function clearEntitiesCache(): void
    {
        $this->entities_cache = null;
    }

    /**
     * Clear in-memory cache for presets.
     * Should be called when presets are modified.
     */
    public function clearPresetsCache(): void
    {
        $this->presets_cache = null;
    }

    /**
     * Clear in-memory cache for presettables.
     * Should be called when presettables are modified.
     */
    public function clearPresettablesCache(): void
    {
        $this->presettables_cache = null;
    }

    /**
     * Clear all in-memory caches.
     */
    public function clearAllCaches(): void
    {
        $this->entities_cache = null;
        $this->presets_cache = null;
        $this->presettables_cache = null;
    }
}
