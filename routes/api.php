<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannersController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\EventsController;
// use App\Http\Controllers\CustomController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MajorsController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsCategoriesController;
use App\Http\Controllers\FeedbacksController;
use App\Http\Controllers\FeedbacksCategoriesController;
use App\Http\Controllers\GlobalConfigController;
use App\Http\Controllers\MajorCompetentController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\VotingCandidateController;
use App\Http\Controllers\VotingController;
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

Route::get('/no-auth/global-config', [PublicController::class, 'globalConfig']);
Route::get('/no-auth/banners', [PublicController::class, 'banners']);
Route::get('/no-auth/vision-mission', [PublicController::class, 'visionMission']);
Route::get('/no-auth/majors', [PublicController::class, 'majorCard']);
Route::get('/no-auth/events', [PublicController::class, 'events']);
Route::get('/no-auth/news', [PublicController::class, 'news']);
Route::get('/no-auth/news-categories', [PublicController::class, 'newsCategories']);
Route::get('/no-auth/votings', [PublicController::class, 'voting']);
Route::post('/no-auth/feedback/create', [PublicController::class, 'createFeedback']);
Route::get('/no-auth/feedback-categories', [PublicController::class, 'feedbackCategoriesDataset']);

Route::group([
    'middleware' => ['setguard:api', 'auth.rest']
], function () {

    // Banners
    Route::get('/banners', [BannersController::class, 'index']);
    Route::get('/banners/{id}', [BannersController::class, 'show']);
    Route::post('/banners/create', [BannersController::class, 'create']);
    Route::put('/banners/update', [BannersController::class, 'update']);
    Route::delete('/banners/delete', [BannersController::class, 'destroy']);

    // Missions
    Route::get('/missions', [MissionController::class, 'index']);
    Route::get('/missions/dataset', [MissionController::class, 'dataset']);
    Route::get('/missions/{id}', [MissionController::class, 'show']);
    Route::post('/missions/create', [MissionController::class, 'create']);
    Route::put('/missions/update', [MissionController::class, 'update']);
    Route::post('/missions/update-status', [MissionController::class, 'updateStatus']);
    Route::delete('/missions/delete', [MissionController::class, 'destroy']);

    // Majors
    Route::get('/majors', [MajorsController::class, 'index']);
    Route::get('/majors/dataset', [MajorsController::class, 'dataset']);
    Route::get('/majors/{id}', [MajorsController::class, 'show']);
    Route::post('/majors/create', [MajorsController::class, 'create']);
    Route::put('/majors/update', [MajorsController::class, 'update']);
    Route::post('/majors/update-status', [MajorsController::class, 'updateStatus']);
    Route::delete('/majors/delete', [MajorsController::class, 'destroy']);

    // Major Competents
    Route::get('/major-competents', [MajorCompetentController::class, 'index']);
    Route::post('/major-competents/create', [MajorCompetentController::class, 'create']);
    Route::put('/major-competents/update', [MajorCompetentController::class, 'update']);
    Route::delete('/major-competents/delete', [MajorCompetentController::class, 'destroy']);

    // Events
    Route::get('/events', [EventsController::class, 'index']);
    Route::get('/events/{id}', [EventsController::class, 'show']);
    Route::post('/events/create', [EventsController::class, 'create']);
    Route::put('/events/update', [EventsController::class, 'update']);
    Route::post('/events/update-highlight', [EventsController::class, 'updateHighlight']);
    Route::delete('/events/delete', [EventsController::class, 'destroy']);

    // News
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/dataset', [NewsController::class, 'dataset']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    Route::post('/news/create', [NewsController::class, 'create']);
    Route::post('/news/update-highlight', [NewsController::class, 'updateHighlight']);
    Route::put('/news/update', [NewsController::class, 'update']);
    Route::delete('/news/delete', [NewsController::class, 'destroy']);

    // News Category
    Route::get('/news-categories', [NewsCategoriesController::class, 'index']);
    Route::get('/news-categories/dataset', [NewsCategoriesController::class, 'dataset']);
    Route::get('/news-categories/{id}', [NewsCategoriesController::class, 'show']);
    Route::post('/news-categories/create', [NewsCategoriesController::class, 'create']);
    Route::put('/news-categories/update', [NewsCategoriesController::class, 'update']);
    Route::delete('/news-categories/delete', [NewsCategoriesController::class, 'destroy']);

    // Votings
    Route::get('/votings', [VotingController::class, 'index']);
    Route::get('/votings/{id}', [VotingController::class, 'show']);
    Route::post('/votings/create', [VotingController::class, 'create']);
    Route::post('/votings/update-highlight', [VotingController::class, 'updateHighlight']);
    Route::put('/votings/update', [VotingController::class, 'update']);
    Route::delete('/votings/delete', [VotingController::class, 'destroy']);

    // Voting Candidates
    Route::get('/voting-candidates', [VotingCandidateController::class, 'index']);
    Route::get('/voting-candidates/{id}', [VotingCandidateController::class, 'show']);
    Route::post('/voting-candidates/create', [VotingCandidateController::class, 'create']);
    Route::put('/voting-candidates/update', [VotingCandidateController::class, 'update']);
    Route::delete('/voting-candidates/delete', [VotingCandidateController::class, 'destroy']);

    // Feedback
    Route::get('/feedbacks', [FeedbacksController::class, 'index']);
    Route::get('/feedbacks/{id}', [FeedbacksController::class, 'show']);
    Route::delete('/feedbacks/delete', [FeedbacksController::class, 'destroy']);

    // Feedbacks Category
    Route::get('/feedbacks-categories', [FeedbacksCategoriesController::class, 'index']);
    Route::get('/feedbacks-categories/dataset', [FeedbacksCategoriesController::class, 'dataset']);
    Route::get('/feedbacks-categories/{id}', [FeedbacksCategoriesController::class, 'show']);
    Route::post('/feedbacks-categories/create', [FeedbacksCategoriesController::class, 'create']);
    Route::put('/feedbacks-categories/update', [FeedbacksCategoriesController::class, 'update']);
    Route::delete('/feedbacks-categories/delete', [FeedbacksCategoriesController::class, 'destroy']);

    // Global Config
    Route::get('/global-config/show', [GlobalConfigController::class, 'show']);
    Route::put('/global-config/update', [GlobalConfigController::class, 'update']);

    // Route dinamis kustom bawaan project (Wajib di bawah route spesifik agar tidak bentrok)
    Route::get('/{model}', [CrudController::class, 'index']);
    Route::get('/{model}/dataset', [CrudController::class, 'dataset']);
    Route::post('/{model}', [CrudController::class, 'create']);
    Route::put('/{model}/{id}', [CrudController::class, 'update']);
    Route::delete('/{model}/{id}', [CrudController::class, 'delete']);
    Route::get('/{model}/{id}', [CrudController::class, 'show']);

    Route::post('file/upload', [UploadController::class, 'upload'])->name("upload")->middleware('auth.rest');


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
