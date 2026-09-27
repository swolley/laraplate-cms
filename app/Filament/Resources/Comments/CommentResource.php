<?php

declare(strict_types=1);

namespace Modules\CMS\Filament\Resources\Comments;

use BackedEnum;
use Coolsam\Modules\Resource;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\CMS\Filament\Resources\Comments\Pages\EditComment;
use Modules\CMS\Filament\Resources\Comments\Pages\ListComments;
use Modules\CMS\Filament\Resources\Comments\Schemas\CommentForm;
use Modules\CMS\Filament\Resources\Comments\Tables\CommentsTable;
use Modules\CMS\Models\Comment;
use Override;
use UnitEnum;

/**
 * Comments are written from the site, so the panel lists and edits them but never creates one.
 * Pending comments and edits are moderated from Modifications.
 */
final class CommentResource extends Resource
{
    #[Override]
    protected static ?string $model = Comment::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    #[Override]
    protected static ?int $navigationSort = 3;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'cms/comments';
    }

    public static function form(Schema $schema): Schema
    {
        return CommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
            'edit' => EditComment::route('/{record}/edit'),
        ];
    }
}
