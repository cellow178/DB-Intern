<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrudController;
// use App\Http\Controllers\CustomController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\VisionMissionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/vision-mission/list', [VisionMissionController::class, 'index']);

Route::get('/news/list', [NewsController::class, 'index']);
Route::get('/news/dataset', [NewsController::class, 'dataset']);
Route::get('/news/{id}', [NewsController::class, 'show']);

Route::get('/news-category/list', [NewsCategoryController::class, 'index']);
Route::get('/news-category/dataset', [NewsCategoryController::class, 'dataset']);
Route::get('/news-category/{id}', [NewsCategoryController::class, 'show']);

Route::group([
    'middleware' => ['setguard:api', 'auth.rest']
], function () {
    // News
    Route::post('/news/create', [NewsController::class, 'create']);
    Route::post('/news/update-highlight', [NewsController::class, 'updateHighlight']);
    Route::put('/news/update/', [NewsController::class, 'update']);
    Route::delete('/news/delete/', [NewsController::class, 'destroy']);

    // News Category
    Route::post('/news-category/create', [NewsCategoryController::class, 'create']);
    Route::put('/news-category/update/', [NewsCategoryController::class, 'update']);
    Route::delete('/news-category/delete/', [NewsCategoryController::class, 'destroy']);

    // Visi & Misi
    Route::post('/vision-mission/create', [VisionMissionController::class, 'create']);
    Route::put('/vision-mission/update/', [VisionMissionController::class, 'update']);
    Route::delete('/vision-mission/delete/', [VisionMissionController::class, 'destroy']);

    // Route dinamis kustom bawaan project (Wajib di bawah route spesifik agar tidak bentrok)
    Route::get('/{model}', [CrudController::class, 'index']);
    Route::get('/{model}/dataset', [CrudController::class, 'dataset']);
    Route::post('/{model}', [CrudController::class, 'create']);
    Route::put('/{model}/{id}', [CrudController::class, 'update']);
    Route::delete('/{model}/{id}', [CrudController::class, 'delete']);
    Route::get('/{model}/{id}', [CrudController::class, 'show']);

    // Route::post('upload', [UploadController::class, 'upload'])->name("upload")->middleware('auth.rest');


    Route::get('/gen-lang/lang', [CrudController::class, 'lang']);
    Route::get('/gen-model/{model}', [CrudController::class, 'generate']);
    Route::get('/gen-module/listmodule', [CrudController::class, 'listModule']);
});

Route::group([
    'middleware' => ['setguard:api']
], function () {
    Route::get('file/{model}/{field}/{id}/{time}', [UploadController::class, 'getFile']);
    Route::get('file/{model}/{field}/{id}/{time}/download', [UploadController::class, 'downloadFile']);
    Route::get('tumb-file/{model}/{field}/{id}/{time}', [UploadController::class, 'getTumbnailFile']);
    Route::get('temp-file/{path}/{time}/{ext}', [UploadController::class, 'getTempFile']);
    Route::get('tumb-temp-file/{path}/{time}/{ext}', [UploadController::class, 'getThumbTempFile']);
    
    Route::post('upload', [UploadController::class, 'upload'])->name("upload");
});