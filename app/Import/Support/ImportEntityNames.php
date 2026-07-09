<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

/**
 * Canonical CMS entity names used by import DTOs and {@see EntityPresetResolver}.
 *
 * Importers should emit these names on every DTO. Legacy singular aliases are
 * normalized here for backward compatibility.
 */
final class ImportEntityNames
{
    public const string CONTENTS = 'contents';

    public const string CATEGORIES = 'categories';

    public const string CONTRIBUTORS = 'contributors';

    public static function normalize(string $entity_name): string
    {
        return match ($entity_name) {
            'post', 'event', 'content', 'multimedia' => self::CONTENTS,
            'category', 'section', 'folder' => self::CATEGORIES,
            'contributor' => self::CONTRIBUTORS,
            default => $entity_name,
        };
    }
}
