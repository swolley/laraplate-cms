<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer;

class MediaFileNamer extends DefaultFileNamer
{
    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        return match ($conversion->getName()) {
            'thumb-high' => $fileName . '-high.' . $conversion->getExtension(),
            'thumb-mid' => $fileName . '-mid.' . $conversion->getExtension(),
            'thumb-low' => $fileName . '-low.' . $conversion->getExtension(),
            'video_thumb-high' => $fileName . '-high.' . $conversion->getExtension(),
            'video_thumb-mid' => $fileName . '-mid.' . $conversion->getExtension(),
            'video_thumb-low' => $fileName . '-low.' . $conversion->getExtension(),
            default => parent::conversionFileName($fileName, $conversion),
        };
    }
}
