<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasPath
{
    abstract protected function getPath(): ?string;

    public function initializeHasPath(): void
    {
        if (! in_array('path', $this->appends, true)) {
            $this->appends[] = 'path';
        }
    }

    protected function getPathPrefix(): string
    {
        return $this->getTable();
    }

    protected function getPathSuffix(): ?string
    {
        $key = $this->getKey();

        return $key ? (string) $key : null;
    }

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
}
