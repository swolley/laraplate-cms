<?php

declare(strict_types=1);

namespace Modules\Cms\Models\Translations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cms\Helpers\HasSlug;
use Modules\Cms\Models\Contributor;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @mixin IdeHelperContributorTranslation
 */
final class ContributorTranslation extends Model implements ITranslated
{
    use HasFactory;
    use HasSlug;

    /**
     * The attributes that are mass assignable.
     */
    #[Override]
    protected $fillable = [
        'contributor_id',
        'locale',
        'slug',
        'components',
    ];

    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The contributor that belongs to the translation.
     *
     * @return BelongsTo<Contributor>
     */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(Contributor::class);
    }

    protected function casts(): array
    {
        return [
            'components' => 'json',
        ];
    }
}
