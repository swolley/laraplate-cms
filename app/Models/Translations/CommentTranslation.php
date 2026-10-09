<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Comment;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

final class CommentTranslation extends Model implements IsPartOfParent, ITranslated
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
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'comment';
    }

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
