<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Fields\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Cms\Filament\Resources\Fields\FieldResource;

final class EditField extends EditRecord
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
