<?php

declare(strict_types=1);

use Modules\CMS\Enums\AiAssistance;

it('defines all ai assistance enum cases with expected values', function (): void {
    expect(AiAssistance::cases())->toHaveCount(5);

    expect(AiAssistance::None->value)->toBe('none');
    expect(AiAssistance::Generated->value)->toBe('generated');
    expect(AiAssistance::Translated->value)->toBe('translated');
    expect(AiAssistance::Edited->value)->toBe('edited');
    expect(AiAssistance::Summarized->value)->toBe('summarized');
});
