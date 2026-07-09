<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Modules\CMS\Import\Dto\ImportContentDto;

/**
 * Emits a single-line message for each content upsert during bulk import.
 */
final class ImportProgressLogger
{
    public function contentImported(ImportContentDto $dto, bool $created): void
    {
        $reference = ($dto->originUrl !== null && $dto->originUrl !== '')
            ? $dto->originUrl
            : "{$dto->sourceType}#{$dto->externalId}";

        $action = $created ? 'imported new content' : 'updated content';
        $message = "{$action} from original url {$reference}";

        Log::info($message);

        if (App::runningInConsole()) {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }
}
