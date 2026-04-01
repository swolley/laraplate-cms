<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Models\Content;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @property int|string $id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property array<string, mixed>|null $components
 */
final class ContentTranslation extends Model implements ITranslated
{
    use HasFactory;
    use HasSlug;

    #[Override]
    protected $table = 'contents_translations';

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'content_id',
        'locale',
        'title',
        'slug',
        'components',
    ];

    #[Override]
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
