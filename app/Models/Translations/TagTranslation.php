<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Tag;
use Modules\Core\Models\Concerns\HasSlug;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @property int|string $id
 * @property string $locale
 * @property string $name
 * @property string $slug
 *
 * @mixin \Eloquent
 * @mixin IdeHelperTagTranslation
 */
final class TagTranslation extends Model implements ITranslated
{
    use HasSlug;

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::TagsTranslations->value;

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
     * @return BelongsTo<Tag, $this>
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
