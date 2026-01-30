<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Models\Tag;
use Modules\Core\Services\Translation\Definitions\ITranslated;

/**
 * @mixin IdeHelperTagTranslation
 */
final class TagTranslation extends Model implements ITranslated
{
    use HasFactory;
    use HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The tag that belongs to the translation.
     *
     * @return BelongsTo<Tag>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    protected function casts(): array
    {
        return [];
    }
}
