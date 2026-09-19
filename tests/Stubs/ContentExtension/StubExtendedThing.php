<?php

declare(strict_types=1);

namespace Modules\CMS\Tests\Stubs\ContentExtension;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CMS\Contracts\ExtendsContent;
use Modules\CMS\Models\Concerns\ExtendsContentTrait;
use Modules\Core\Search\Schema\FieldType;
use Override;

/**
 * Test-only content extender exercising the seam generically, before any real consumer exists.
 *
 * A real extender (e.g. an Ecommerce `Product`) extends `Modules\Core\Overrides\Model`; the seam
 * trait is base-class agnostic, so this stub stays a plain Eloquent model with `SoftDeletes` to keep
 * the tests focused on the seam rather than Core model behaviour (ACL, validation, versioning).
 *
 * Its table `cms_stub_extended_things` is created per test via `Schema::create` in `beforeEach`.
 *
 * @property int|string|null $content_id
 * @property string|null $brand
 * @property string|null $sku
 */
final class StubExtendedThing extends Model implements ExtendsContent
{
    use ExtendsContentTrait;
    use SoftDeletes;

    protected $table = 'cms_stub_extended_things';

    /**
     * @var list<string>
     */
    protected $fillable = ['content_id', 'brand', 'sku'];

    #[Override]
    public function contentAlias(): string
    {
        return 'cms.stub_extended';
    }

    #[Override]
    public function searchableExtension(): array
    {
        return [
            'brand' => $this->brand,
            'sku' => $this->sku,
        ];
    }

    #[Override]
    public function searchableExtensionMapping(): array
    {
        return [
            'brand' => FieldType::Keyword,
            'sku' => FieldType::Keyword,
        ];
    }
}
