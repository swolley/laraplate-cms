<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Templates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Templates\TemplateResource;
use Override;

final class EditTemplate extends EditRecord
{
    #[Override]
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
