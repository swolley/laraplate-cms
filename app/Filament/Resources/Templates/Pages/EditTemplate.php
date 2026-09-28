<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Templates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Templates\TemplateResource;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Override;

final class EditTemplate extends EditRecord
{
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
