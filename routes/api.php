<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CrudController;
// use App\Http\Controllers\CustomController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsCategoryController;
use App\Http\Controllers\FeedbackCategoryController;
use App\Http\Controllers\MajorCompetentController;
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

    // Banners
    Route::get('/banners', [BannerController::class, 'index']);
    Route::get('/banners/{id}', [BannerController::class, 'show']);
    Route::post('/banners/create', [BannerController::class, 'create']);
    Route::put('/banners/update', [BannerController::class, 'update']);
    Route::delete('/banners/delete', [BannerController::class, 'destroy']);

    // Missions
    Route::get('/missions', [MissionController::class, 'index']);
    Route::get('/missions/dataset', [MissionController::class, 'dataset']);
    Route::get('/missions/{id}', [MissionController::class, 'show']);
    Route::post('/missions/create', [MissionController::class, 'create']);
    Route::put('/missions/update/', [MissionController::class, 'update']);
    Route::post('/missions/update-status/', [MissionController::class, 'updateStatus']);
    Route::delete('/missions/delete/', [MissionController::class, 'destroy']);

    // Majors
    Route::get('/majors', [MajorController::class, 'index']);
    Route::get('/majors/dataset', [MajorController::class, 'dataset']);
    Route::get('/majors/{id}', [MajorController::class, 'show']);
    Route::post('/majors/create', [MajorController::class, 'create']);
    Route::put('/majors/update/', [MajorController::class, 'update']);
    Route::post('/majors/update-status/', [MajorController::class, 'updateStatus']);
    Route::delete('/majors/delete/', [MajorController::class, 'destroy']);

    // Major Competents
    Route::get('/major-competents', [MajorCompetentController::class, 'index']);
    Route::post('/major-competents/create', [MajorCompetentController::class, 'create']);
    Route::put('/major-competents/update', [MajorCompetentController::class, 'update']);
    Route::delete('/major-competents/delete/', [MajorCompetentController::class, 'destroy']);

    // News
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/dataset', [NewsController::class, 'dataset']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::post('/news/create', [NewsController::class, 'create']);
    Route::post('/news/update-highlight', [NewsController::class, 'updateHighlight']);
    Route::put('/news/update/', [NewsController::class, 'update']);
    Route::delete('/news/delete/', [NewsController::class, 'destroy']);

    // News Category
    Route::get('/news-categories', [NewsCategoryController::class, 'index']);
    Route::get('/news-categories/dataset', [NewsCategoryController::class, 'dataset']);
    Route::get('/news-categories/{id}', [NewsCategoryController::class, 'show']);
    Route::post('/news-categories/create', [NewsCategoryController::class, 'create']);
    Route::put('/news-categories/update/', [NewsCategoryController::class, 'update']);
    Route::delete('/news-categories/delete/', [NewsCategoryController::class, 'destroy']);

    // Feedbacks Category
    Route::get('/feedbacks-categories', [FeedbackCategoryController::class, 'index']);
    Route::get('/feedbacks-categories/dataset', [FeedbackCategoryController::class, 'dataset']);
    Route::get('/feedbacks-categories/{id}', [FeedbackCategoryController::class, 'show']);
    Route::post('/feedbacks-categories/create', [FeedbackCategoryController::class, 'create']);
    Route::put('/feedbacks-categories/update/', [FeedbackCategoryController::class, 'update']);
    Route::delete('/feedbacks-categories/delete/', [FeedbackCategoryController::class, 'destroy']);

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
