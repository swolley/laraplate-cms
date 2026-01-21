<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Models\Content;
use Modules\Core\Services\Translation\Definitions\ITranslated;

/**
 * @mixin IdeHelperContentTranslation
 */
final class ContentTranslation extends Model implements ITranslated
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'content_id',
        'locale',
        'title',
        'slug',
        'components',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The content that belongs to the translation.
     *
     * @return BelongsTo<Content>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    protected function casts(): array
    {
        return [
            'components' => 'json',
        ];
    }
}
