<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Stubs\ContentExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CMS\Contracts\ExtendsContent;
use Modules\CMS\Models\Concerns\ExtendsContentTrait;
use Override;

/**
 * Test-only extender whose content is **optional** (`contentIsMandatory() === false`), so a direct
 * content deletion orphans it (nulls `content_id`) rather than deleting it. Exercises the seam's
 * optional/orphan lifecycle branch (C9).
 *
 * @property int|string|null $content_id
 * @property string|null $label
 */
final class StubOptionalExtendedThing extends Model implements ExtendsContent
{
    use ExtendsContentTrait;
    use SoftDeletes;

    protected $table = 'cms_stub_optional_extended_things';

    /**
     * @var list<string>
     */
    protected $fillable = ['content_id', 'label'];

    #[Override]
    public function contentAlias(): string
    {
        return 'cms.stub_optional';
    }

    #[Override]
    public function contentIsMandatory(): bool
    {
        return false;
    }

    #[Override]
    public function searchableExtension(): array
    {
        return ['label' => $this->label];
    }

    #[Override]
    public function searchableExtensionMapping(): array
    {
        return ['label' => ['type' => 'keyword']];
    }
}
