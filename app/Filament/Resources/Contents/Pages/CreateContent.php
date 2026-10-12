<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Contents\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Filament\Resources\Contents\ContentResource;
use Modules\Core\Filament\Utils\AssignsAttributesOwnedByTheForm;
use Modules\Core\Filament\Utils\HasCloseOrCancelFormAction;
use Override;

final class CreateContent extends CreateRecord
{
    use AssignsAttributesOwnedByTheForm;
    use HasCloseOrCancelFormAction;

    #[Override]
    protected static string $resource = ContentResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $record = new (self::getModel());

        $this->fillFromFormState($record, $data);
        $record->save();

        return $record;
    }
}
