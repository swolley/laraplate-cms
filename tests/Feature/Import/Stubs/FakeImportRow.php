<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Feature\Import\Stubs;

use Illuminate\Database\Eloquent\Model;

final class FakeImportRow extends Model
{
    public $timestamps = false;

    protected $table = FakeBulkImporter::TABLE;
}
