<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Unit\Filament\Utils;

use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

abstract class CMSFilamentHarnessBase
{
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        return new Collection();
    }

    protected function makeTable(): Table
    {
        throw new \RuntimeException('Not implemented in test harness');
    }
}

