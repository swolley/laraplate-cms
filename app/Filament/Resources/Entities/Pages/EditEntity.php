<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Entities\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Entities\EntityResource;
use Override;

final class EditEntity extends EditRecord
{
    #[Override]
    protected static string $resource = EntityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
