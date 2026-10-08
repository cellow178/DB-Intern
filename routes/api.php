<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BannersController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\EventsController;
// use App\Http\Controllers\CustomController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\MajorsController;
use App\Http\Controllers\MajorGalleryController;
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
Route::get('/no-auth/majors/{slug}', [PublicController::class, 'majorDetail']);
Route::get('/no-auth/events', [PublicController::class, 'events']);
Route::get('/no-auth/news', [PublicController::class, 'news']);
Route::get('/no-auth/news-categories', [PublicController::class, 'newsCategories']);
Route::get('/no-auth/votings', [PublicController::class, 'voting']);
Route::post('/no-auth/feedback/create', [PublicController::class, 'createFeedback']);
Route::get('/no-auth/feedback-categories', [PublicController::class, 'feedbackCategoriesDataset']);

Route::group([
    'middleware' => ['setguard:api', 'auth.rest']
], function () {
     Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Banners
    Route::get('/banners', [BannersController::class, 'index'])
        ->middleware('permission:view-banners');
    Route::get('/banners/{id}', [BannersController::class, 'show'])
        ->middleware('permission:show-banners');
    Route::post('/banners/create', [BannersController::class, 'create'])
        ->middleware('permission:create-banners');
    Route::put('/banners/update', [BannersController::class, 'update'])
        ->middleware('permission:update-banners');
    Route::delete('/banners/delete', [BannersController::class, 'destroy'])
        ->middleware('permission:delete-banners');

    // Missions
    Route::get('/missions', [MissionController::class, 'index'])
        ->middleware('permission:view-missions');
    Route::get('/missions/dataset', [MissionController::class, 'dataset']);
    Route::get('/missions/{id}', [MissionController::class, 'show'])
        ->middleware('permission:show-missions');
    Route::post('/missions/create', [MissionController::class, 'create'])
        ->middleware('permission:create-missions');
    Route::put('/missions/update', [MissionController::class, 'update'])
        ->middleware('permission:update-missions');
    Route::post('/missions/update-status', [MissionController::class, 'updateStatus'])
        ->middleware('permission:update-missions');
    Route::delete('/missions/delete', [MissionController::class, 'destroy'])
        ->middleware('permission:delete-missions');

    // Majors
    Route::get('/majors', [MajorsController::class, 'index'])
        ->middleware('permission:view-majors');
    Route::get('/majors/dataset', [MajorsController::class, 'dataset']);
    Route::get('/majors/{id}', [MajorsController::class, 'show'])
        ->middleware('permission:show-majors');
    Route::post('/majors/create', [MajorsController::class, 'create'])
        ->middleware('permission:create-majors');
    Route::put('/majors/update', [MajorsController::class, 'update'])
        ->middleware('permission:update-majors');
    Route::post('/majors/update-status', [MajorsController::class, 'updateStatus'])
        ->middleware('permission:update-majors');
    Route::delete('/majors/delete', [MajorsController::class, 'destroy'])
        ->middleware('permission:delete-majors');

    // Major Competents
    Route::get('/major-competents', [MajorCompetentController::class, 'index'])
        ->middleware('permission:view-major-competents');
    Route::get('/major-competents/{id}', [MajorCompetentController::class, 'show'])
        ->middleware('permission:show-major-competents');
    Route::post('/major-competents/create', [MajorCompetentController::class, 'create'])
        ->middleware('permission:create-major-competents');
    Route::put('/major-competents/update', [MajorCompetentController::class, 'update'])
        ->middleware('permission:update-major-competents');
    Route::delete('/major-competents/delete', [MajorCompetentController::class, 'destroy'])
        ->middleware('permission:delete-major-competents');

    // Major Gallery
    Route::get('/major-gallery', [MajorGalleryController::class, 'index'])
        ->middleware('permission:view-major-gallery');
    Route::get('/major-gallery/{id}', [MajorGalleryController::class, 'show'])
        ->middleware('permission:show-major-gallery');
    Route::post('/major-gallery/create', [MajorGalleryController::class, 'create'])
        ->middleware('permission:create-major-gallery');
    Route::put('/major-gallery/update', [MajorGalleryController::class, 'update'])
        ->middleware('permission:update-major-gallery');
    Route::delete('/major-gallery/delete', [MajorGalleryController::class, 'delete'])
        ->middleware('permission:delete-major-gallery');

    // Events
    Route::get('/events', [EventsController::class, 'index'])
        ->middleware('permission:view-events');
    Route::get('/events/{id}', [EventsController::class, 'show'])
        ->middleware('permission:show-events');
    Route::post('/events/create', [EventsController::class, 'create'])
        ->middleware('permission:create-events');
    Route::put('/events/update', [EventsController::class, 'update'])
        ->middleware('permission:update-events');
    Route::post('/events/update-highlight', [EventsController::class, 'updateHighlight'])
        ->middleware('permission:update-events');
    Route::delete('/events/delete', [EventsController::class, 'destroy'])
        ->middleware('permission:delete-events');

    // News
    Route::get('/news', [NewsController::class, 'index'])
        ->middleware('permission:view-news');
    Route::get('/news/dataset', [NewsController::class, 'dataset']);
    Route::get('/news/{id}', [NewsController::class, 'show'])
        ->middleware('permission:show-news');
    Route::post('/news/create', [NewsController::class, 'create'])
        ->middleware('permission:create-news');
    Route::post('/news/update-highlight', [NewsController::class, 'updateHighlight'])
        ->middleware('permission:update-news');
    Route::put('/news/update', [NewsController::class, 'update'])
        ->middleware('permission:update-news');
    Route::delete('/news/delete', [NewsController::class, 'destroy'])
        ->middleware('permission:delete-news');

    // News Category
    Route::get('/news-categories', [NewsCategoriesController::class, 'index'])
        ->middleware('permission:view-news-categories');
    Route::get('/news-categories/dataset', [NewsCategoriesController::class, 'dataset']);
    Route::get('/news-categories/{id}', [NewsCategoriesController::class, 'show'])
        ->middleware('permission:show-news-categories');
    Route::post('/news-categories/create', [NewsCategoriesController::class, 'create'])
        ->middleware('permission:create-news-categories');
    Route::put('/news-categories/update', [NewsCategoriesController::class, 'update'])
        ->middleware('permission:update-news-categories');
    Route::delete('/news-categories/delete', [NewsCategoriesController::class, 'destroy'])
        ->middleware('permission:delete-news-categories');

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
    Route::get('/feedbacks', [FeedbacksController::class, 'index'])
        ->middleware('permission:view-feedbacks');
    Route::get('/feedbacks/{id}', [FeedbacksController::class, 'show'])
        ->middleware('permission:show-feedbacks');
    Route::delete('/feedbacks/delete', [FeedbacksController::class, 'destroy'])
        ->middleware('permission:delete-feedbacks');

    // Feedbacks Category
    Route::get('/feedbacks-categories', [FeedbacksCategoriesController::class, 'index'])
        ->middleware('permission:view-feedbacks-categories');
    Route::get('/feedbacks-categories/dataset', [FeedbacksCategoriesController::class, 'dataset']);
    Route::get('/feedbacks-categories/{id}', [FeedbacksCategoriesController::class, 'show'])
        ->middleware('permission:show-feedbacks-categories');
    Route::post('/feedbacks-categories/create', [FeedbacksCategoriesController::class, 'create'])
        ->middleware('permission:create-feedbacks-categories');
    Route::put('/feedbacks-categories/update', [FeedbacksCategoriesController::class, 'update'])
        ->middleware('permission:update-feedbacks-categories');
    Route::delete('/feedbacks-categories/delete', [FeedbacksCategoriesController::class, 'destroy'])
        ->middleware('permission:delete-feedbacks-categories');

    // Global Config
    Route::get('/global-config/show', [GlobalConfigController::class, 'show'])
        ->middleware('permission:show-global-config');
    Route::put('/global-config/update', [GlobalConfigController::class, 'update'])
        ->middleware('permission:update-global-config');
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
