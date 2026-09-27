<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Comments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\CMS\Filament\Resources\Comments\CommentResource;
use Modules\CMS\Models\Comment;
use Override;

/**
 * The comment text lives on the translation of the current locale, not on a column,
 * so it is read from and written back through the model's translation accessors.
 */
final class EditComment extends EditRecord
{
    #[Override]
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Comment $comment */
        $comment = $this->getRecord();

        return [...$data, 'body' => $comment->body];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Comment $record */
        $record->body = (string) ($data['body'] ?? '');
        $record->save();

        return $record;
    }
}
