<?php

declare(strict_types=1);

namespace Modules\CMS\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Models\Content;

/**
 * Implemented by a domain model that extends a {@see Content} through the content-extension seam.
 *
 * The extender lives in its own table with a `content_id` foreign key; CMS knows it only by its
 * stable alias, resolved through the {@see \Modules\CMS\Services\ContentExtenderRegistry}. CMS never
 * references the concrete extender class.
 */
interface ExtendsContent
{
    /**
     * The stable morph alias for this extender (convention `module.model`, e.g. `ecommerce.product`).
     */
    public function contentAlias(): string;

    /**
     * The back-relation to the extended content, with the hide scope removed so it resolves.
     *
     * @return BelongsTo<Content, Model>
     */
    public function content(): BelongsTo;

    /**
     * The extender's own data, merged into the content's search document under a nested `extension`.
     *
     * @return array<string, mixed>
     */
    public function searchableExtension(): array;

    /**
     * The mapping fragment CMS composes into the `contents` index for this extender's `extension`.
     *
     * @return array<string, mixed>
     */
    public function searchableExtensionMapping(): array;
}
