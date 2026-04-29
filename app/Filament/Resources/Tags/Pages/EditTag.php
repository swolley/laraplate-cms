<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Tags\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CMS\Filament\Resources\Tags\TagResource;
use Override;

final class EditTag extends EditRecord
{
    #[Override]
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
