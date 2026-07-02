<?php

declare(strict_types=1);

namespace Modules\CMS\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CMS\Database\Factories\ContentReferenceFactory;
use Modules\CMS\Enums\CMSTables;
use Modules\Core\Models\Concerns\SortableTrait;
use Modules\Core\Overrides\Model;
use Override;
use Spatie\EloquentSortable\Sortable;

/**
 * @property int|string $id
 * @property int $content_id
 * @property string $label
 * @property string|null $url
 * @property int $order_column
 *
 * @mixin \Eloquent
 */
final class ContentReference extends Model implements Sortable
{
    use SortableTrait;

    /**
     * @var string
     */
    #[Override]
    protected $table = CMSTables::ContentsReferences->value; // cms_contents_references

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'content_id',
        'label',
        'url',
        'order_column',
    ];

    /**
     * @var list<string>
     */
    #[Override]
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $sortable = [
        'order_column_name' => 'order_column',
        'sort_when_creating' => true,
    ];

    /**
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * @return Builder<static>
     */
    public function buildSortQuery(): Builder
    {
        return static::query()->where('content_id', $this->content_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules['create'] = array_merge($rules['create'], [
            'content_id' => ['required', 'integer', 'exists:' . CMSTables::Contents->value . ',id'],
            'label' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2048'],
            'order_column' => ['sometimes', 'integer', 'min:0'],
        ]);
        $rules['update'] = array_merge($rules['update'], [
            'content_id' => ['sometimes', 'integer', 'exists:' . CMSTables::Contents->value . ',id'],
            'label' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'order_column' => ['sometimes', 'integer', 'min:0'],
        ]);

        return $rules;
    }

    #[Override]
    protected static function newFactory(): ContentReferenceFactory
    {
        return ContentReferenceFactory::new();
    }
}
