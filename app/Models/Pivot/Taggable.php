<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Tag;
use Override;

/**
 * The assignment of a tag to any taggable model (content, …). Modelled
 * explicitly so the moment a tag was attached (or last touched) is recorded and
 * observable, rather than living in an anonymous polymorphic pivot row.
 *
 * @property int $tag_id
 * @property int $taggable_id
 * @property string $taggable_type
 * @mixin \Eloquent
 * @mixin IdeHelperTaggable
 */
final class Taggable extends MorphPivot
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = true;

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::Taggables->value;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'tag_id' => 'integer',
            'taggable_id' => 'integer',
        ];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
