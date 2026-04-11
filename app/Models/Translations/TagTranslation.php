<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Models\Tag;
use Modules\Core\Helpers\HasSlug;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @mixin IdeHelperTagTranslation
 */
final class TagTranslation extends Model implements ITranslated
{
    use HasSlug;

    #[Override]
    protected $table = 'tags_translations';

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
    ];

    #[Override]
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
