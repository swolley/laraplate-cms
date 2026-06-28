<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Comment;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @property int|string $id
 * @property string $locale
 * @property string|null $body
 * @mixin \Eloquent
 * @mixin IdeHelperCommentTranslation
 */
final class CommentTranslation extends Model implements ITranslated
{
    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::CommentsTranslations->value;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'comment_id',
        'locale',
        'body',
    ];

    /**
     * @var list<string>
     */
    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
