<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Database\Factories\ContentRatingFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Enums\CoreTables;
use Modules\Core\Models\User;
use Modules\Core\Overrides\Model;
use Override;

/**
 * @property int $content_id
 * @property int $user_id
 * @property int|null $comment_id
 * @property int $score
 * @mixin \Eloquent
 * @mixin IdeHelperContentRating
 */
final class ContentRating extends Model
{
    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::ContentRatings->value;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'content_id',
        'user_id',
        'comment_id',
        'score',
    ];

    /**
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(user_class());
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        $rules[Model::DEFAULT_RULE] = array_merge($rules[Model::DEFAULT_RULE] ?? [], [
            'content_id' => 'required|integer|exists:' . CMSTables::Contents->value . ',id',
            'user_id' => 'required|integer|exists:' . CoreTables::Users->value . ',id',
            'comment_id' => 'nullable|integer|exists:' . CMSTables::Comments->value . ',id',
            'score' => 'required|integer|min:1|max:5',
        ]);

        return $rules;
    }

    protected static function newFactory(): ContentRatingFactory
    {
        return ContentRatingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'content_id' => 'integer',
            'user_id' => 'integer',
            'comment_id' => 'integer',
            'score' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'datetime',
        ];
    }
}
