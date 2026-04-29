<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\CMS\Http\Controllers\ContentsController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::group(['prefix' => '{relation}'], static function (): void {
    $cached_names = [];
    try {
        /** @var mixed $from_cache */
        $from_cache = Cache::get('entities', collect());
        if ($from_cache instanceof Collection) {
            $cached_names = $from_cache->pluck('name')->toArray();
        }
    } catch (\Throwable) {
        try {
            Cache::forget('entities');
        } catch (\Throwable) {
            // Ignore: driver may be unavailable during early bootstrap.
        }
    }

    $entities = ['contents', ...$cached_names];
    $entities = array_map(static fn ($entity) => Str::plural($entity), $entities);
    Route::get('/{value}/{entity}', [ContentsController::class, 'getContentsByRelation'])->name('relation.contents')->whereIn('relation', ['tags', 'categories', 'locations', 'contributors'])->whereIn('entity', $entities);
});
