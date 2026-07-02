<?php

declare(strict_types=1);

namespace Modules\CMS\Enums;

enum AiAssistance: string
{
    case None = 'none';
    case Generated = 'generated';
    case Translated = 'translated';
    case Edited = 'edited';
    case Summarized = 'summarized';
}
