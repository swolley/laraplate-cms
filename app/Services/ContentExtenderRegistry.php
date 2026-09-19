<?php

declare(strict_types=1);

namespace Modules\CMS\Services;

use InvalidArgumentException;
use Modules\CMS\Contracts\ExtendsContent;

/**
 * In-memory registry mapping a content-extension alias to its extender class.
 *
 * Populated at boot by each extender module's service provider — never seeded from a table and never
 * keyed by entity id (which differs per install and does not exist on a fresh app). It holds only
 * content extenders, unlike Laravel's global morph map. On an install with no extender registered it
 * is empty and every extension code path is a no-op.
 */
final class ContentExtenderRegistry
{
    /**
     * @var array<string, class-string<ExtendsContent>>
     */
    private array $extenders = [];

    /**
     * @param  class-string<ExtendsContent>  $extender
     */
    public function register(string $alias, string $extender): void
    {
        $this->extenders[$alias] = $extender;
    }

    public function has(string $alias): bool
    {
        return array_key_exists($alias, $this->extenders);
    }

    /**
     * @return class-string<ExtendsContent>
     */
    public function resolve(string $alias): string
    {
        if (! $this->has($alias)) {
            throw new InvalidArgumentException("No content extender registered for alias [{$alias}].");
        }

        return $this->extenders[$alias];
    }

    /**
     * @return list<string>
     */
    public function aliases(): array
    {
        return array_keys($this->extenders);
    }
}
