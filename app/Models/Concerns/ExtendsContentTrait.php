<?php

declare(strict_types=1);

namespace Modules\CMS\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Modules\CMS\Models\Content;
use Modules\CMS\Scopes\HidesExtendedContent;
use Override;

/**
 * Wires a domain model as a content extender for the content-extension seam.
 *
 * The using class must implement {@see \Modules\CMS\Contracts\ExtendsContent} and own a
 * `content_id` foreign key (unique — one extender per content, C15). The trait provides the
 * back-relation with the hide scope removed (C8), always-loads the content, and stamps
 * `extended_type` on the content when the extender is saved (C2). It is base-class agnostic:
 * a real extender extends `Modules\Core\Overrides\Model`, this test-friendly wiring works on any
 * Eloquent model.
 *
 * @property int|string|null $content_id
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 * @phpstan-require-implements \Modules\CMS\Contracts\ExtendsContent
 */
trait ExtendsContentTrait
{
    private ?Content $tempContent = null;

    /**
     * Always eager-load the extended content alongside the extender.
     */
    public function initializeExtendsContentTrait(): void
    {
        if (! in_array('content', $this->with, true)) {
            $this->with[] = 'content';
        }
    }

    /**
     * The extended content, with the hide scope removed so the owner can always resolve it (C8).
     *
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id')
            ->withoutGlobalScope(HidesExtendedContent::class);
    }

    /**
     * Stage a content to be persisted and linked when this extender is saved.
     */
    public function setTempContent(?Content $content): void
    {
        $this->tempContent = $content;
    }

    /**
     * Persist the staged content (stamping `extended_type`), link it, then save the extender —
     * all in one transaction. Mirrors CMS `Contributor`'s `save()` bridge to its `User`.
     *
     * @param  array<string, mixed>  $options
     */
    #[Override]
    public function save(array $options = []): bool
    {
        if (! $this->tempContent instanceof Content) {
            return parent::save($options);
        }

        return DB::transaction(function () use ($options): bool {
            $content = $this->tempContent;

            // extended_type is guarded; set it directly so it is written only through this path (C17).
            $content->extended_type = $this->contentAlias();
            $content->save();

            $this->content_id = $content->getKey();
            $this->tempContent = null;

            $saved = parent::save($options);

            $this->setRelation('content', $content);

            return $saved;
        });
    }
}
