<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Contributors\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Contributors\ContributorResource;
use Override;

final class EditContributor extends EditRecord
{
    #[Override]
    protected static string $resource = ContributorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
