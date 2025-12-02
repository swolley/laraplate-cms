<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasPath
{
    /**
     * get path for full path.
     */
    abstract protected function getPath(): ?string;

    /** @class-property string|null $slug */
    /**
     * get prefix for full path.
     */
    protected function getPathPrefix(): string
    {
        return $this->getTable();
    }

    /**
     * get suffix for full path.
     */
    protected function getPathSuffix(): ?string
    {
        $key = $this->getKey();

        return $key ? (string) $key : null;
    }

    /**
     * get full path.
     */
    protected function getFullPath(): string
    {
        $suffix = $this->getPathSuffix();
        $prefix = $this->getPathPrefix();
        $path = $this->getPath();

        return str_replace('//', '/', $prefix . '/' . ($path ?: 'undefined') . '/' . ($this->slug ?? 'undefined') . ($suffix ? '/' . $suffix : ''));
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFullPath(),
        );
    }

    public function initializeHasPath(): void
    {
        if (! in_array('path', $this->appends, true)) {
            $this->appends[] = 'path';
        }
    }
}
