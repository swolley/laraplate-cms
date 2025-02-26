<?php

namespace Modules\Cms\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperCategorizable
 */
class Categorizable extends Pivot
{
	protected $table = 'categorizables';

	protected function casts()
	{
		return [
			'created_at' => 'immutable_datetime',
			'updated_at' => 'datetime',
		];
	}
}
