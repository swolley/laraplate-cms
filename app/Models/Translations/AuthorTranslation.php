<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Models\Author;

/**
 * @mixin IdeHelperAuthorTranslation
 */
final class AuthorTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'author_id',
        'locale',
        'slug',
        'components',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The author that belongs to the translation.
     *
     * @return BelongsTo<Author>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    protected function casts(): array
    {
        return [
            'components' => 'json',
        ];
    }
}
