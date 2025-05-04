<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\ContentsController;

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

// Route::group(['prefix' => 'locations'], function () {
//     Route::get('/{value}/contents', [ContentsController::class, 'getContentsByLocation'])->name('locations.contents');
//     Route::get('/{value}/{entity}', [ContentsController::class, 'getEntityContentsByLocation'])->name('locations.entity');
// });
Route::group(['prefix' => '{relation}'], function (): void {
    $entities = ['contents', ...Cache::get('entities', collect())->pluck('name')->toArray()];
    $entities = array_map(fn ($entity) => Str::plural($entity), $entities);
    Route::get('/{value}/{entity}', [ContentsController::class, 'getContentsByRelation'])->name('relation.contents')->whereIn('relation', ['tags', 'categories', 'locations', 'authors'])->whereIn('entity', $entities);
});
