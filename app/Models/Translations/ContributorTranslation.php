<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Translations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Enums\CMSTables;
use Modules\CMS\Models\Contributor;
use Modules\Core\Contracts\IsPartOfParent;
use Modules\Core\Models\Concerns\HasSlug;
use Modules\Core\Models\Concerns\TouchesOwnerOnChange;
use Modules\Core\Overrides\Model;
use Modules\Core\Services\Translation\Definitions\ITranslated;
use Override;

final class ContributorTranslation extends Model implements IsPartOfParent, ITranslated
{
    use HasSlug;
    use TouchesOwnerOnChange;

    /**
     * @var string
     */
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

    /**
     * The relation to the record this one only exists inside, whose visibility it inherits.
     */
    #[Override]
    public function parentRelation(): string
    {
        return 'contributor';
    }

    /**
     * The contributor that belongs to the translation.
     *
     * @return BelongsTo<Contributor, $this>
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

    /**
     * The record this translation belongs to: its `updated_at` moves when the translation changes (D30).
     *
     * @return array{0: \Illuminate\Database\Eloquent\Model, 1: int|string}|null
     */
    protected function ownerToTouch(): ?array
    {
        return $this->ownerThrough($this->contributor());
    }
}
