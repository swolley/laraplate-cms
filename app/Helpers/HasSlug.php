<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasSlug
{
	public static function bootHasSlug()
	{
		static::saving(function (Model $model) {
			if (!$model->getRawOriginal('slug') && !$model->isDirty('slug')) {
				$model->slug = $model->generateSlug();
			}
		});
	}

	public static function slugFields(): array
	{
		return ['name'];
	}

	protected function slugValues(): array
	{
		return array_map(fn($name) => $this->{$name}, $this->slugFields());
	}

	public function generateSlug(): string
	{
		$slugger = config('cms.slugger', '\Illuminate\Support\Str::slug');
		$slug = array_reduce($this->slugValues(), fn($slug, $value) => $slug . '-' . ($value ? trim((string) $value) : ''), '');

		return call_user_func($slugger, ltrim($slug, '-'));
	}

	protected function slug(): Attribute
	{
		return Attribute::make(
			get: fn() => $this->attributes['slug'] ?? $this->generateSlug(),
		);
	}
}
