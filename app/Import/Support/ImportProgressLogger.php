<?php

declare(strict_types=1);

namespace Modules\CMS\Import\Support;

use Illuminate\Support\Facades\Log;
use Modules\CMS\Import\Dto\ImportContentDto;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Emits a single-line message for each content upsert during bulk import.
 */
final class ImportProgressLogger
{
    public function contentImported(
        ImportContentDto $dto,
        bool $created,
        ?OutputInterface $output = null,
    ): void {
        $reference = ($dto->originUrl !== null && $dto->originUrl !== '')
            ? $dto->originUrl
            : "{$dto->sourceType}#{$dto->externalId}";

        $action = $created ? 'imported new content' : 'updated content';
        $message = "{$action} from original url {$reference}";

        Log::info($message);

        // Prefer writeln with info tags over SymfonyStyle::info(), which renders a block.
        $output?->writeln("<info>{$message}</info>");
    }
}
