<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\AiAssistance;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Content;
use Modules\Core\Models\Concerns\HasSlug;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @property int|string $id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property array<string, mixed>|null $components
 * @property AiAssistance $ai_assistance
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin \Eloquent
 * @mixin IdeHelperContentTranslation
 */
final class ContentTranslation extends Model implements ITranslated
{
    use HasSlug;

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::ContentsTranslations->value;

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
        'ai_assistance',
    ];

    #[Override]
    protected $attributes = [
        'components' => '[]',
        'ai_assistance' => 'none',
    ];

    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The content that belongs to the translation.
     *
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    protected function casts(): array
    {
        return [
            'components' => 'json',
            'ai_assistance' => AiAssistance::class,
        ];
    }
}
