<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Contributor;
use Modules\Core\Helpers\HasSlug;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

/**
 * @mixin \Eloquent
 * @mixin IdeHelperContributorTranslation
 */
final class ContributorTranslation extends Model implements ITranslated
{
    use HasSlug;

    #[Override]
    protected $table = CMSTables::ContributorsTranslations->value;

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
