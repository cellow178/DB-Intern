<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrudController;
// use App\Http\Controllers\CustomController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MissionsController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsCategoriesController;
use App\Http\Controllers\FeedbacksCategoriesController;
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
Route::group([
    'middleware' => ['setguard:api', 'auth.rest']
], function () {

    // Missions
    Route::get('/missions/list', [MissionsController::class, 'index']);
    Route::get('/missions/dataset', [MissionsController::class, 'dataset']);
    Route::get('/missions/{id}', [MissionsController::class, 'show']);
    Route::post('/missions/create', [MissionsController::class, 'create']);
    Route::put('/missions/update/', [MissionsController::class, 'update']);
    Route::post('/missions/update-status/', [MissionsController::class, 'updateStatus']);
    Route::delete('/missions/delete/', [MissionsController::class, 'destroy']);

    // News
    Route::get('/news/list', [NewsController::class, 'index']);
    Route::get('/news/dataset', [NewsController::class, 'dataset']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::post('/news/create', [NewsController::class, 'create']);
    Route::post('/news/update-highlight', [NewsController::class, 'updateHighlight']);
    Route::put('/news/update/', [NewsController::class, 'update']);
    Route::delete('/news/delete/', [NewsController::class, 'destroy']);

    // News Category
    Route::get('/news-categories/list', [NewsCategoriesController::class, 'index']);
    Route::get('/news-categories/dataset', [NewsCategoriesController::class, 'dataset']);
    Route::get('/news-categories/{id}', [NewsCategoriesController::class, 'show']);
    Route::post('/news-categories/create', [NewsCategoriesController::class, 'create']);
    Route::put('/news-categories/update/', [NewsCategoriesController::class, 'update']);
    Route::delete('/news-categories/delete/', [NewsCategoriesController::class, 'destroy']);

    // Feedbacks Category
    Route::get('/feedbacks-categories/list', [FeedbacksCategoriesController::class, 'index']);
    Route::get('/feedbacks-categories/dataset', [FeedbacksCategoriesController::class, 'dataset']);
    Route::get('/feedbacks-categories/{id}', [FeedbacksCategoriesController::class, 'show']);
    Route::post('/feedbacks-categories/create', [FeedbacksCategoriesController::class, 'create']);
    Route::put('/feedbacks-categories/update/', [FeedbacksCategoriesController::class, 'update']);
    Route::delete('/feedbacks-categories/delete/', [FeedbacksCategoriesController::class, 'destroy']);

    // Global Config
    Route::get('/global-config/show', [App\Http\Controllers\GlobalConfigController::class, 'show']);
    Route::put('/global-config/update', [App\Http\Controllers\GlobalConfigController::class, 'update']);

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