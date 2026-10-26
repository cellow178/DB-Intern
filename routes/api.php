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
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VotingCandidateController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\BoothController;
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

// Bilik TPS publik (tanpa login akun — pakai pass bilik / ballot token).
Route::get('/no-auth/booth/open', [BoothController::class, 'openBooth']);
Route::post('/no-auth/booth/login', [BoothController::class, 'boothLogin']);
Route::post('/no-auth/booth/cast', [BoothController::class, 'cast']);

// Lupa password (publik, rate-limit di controller). Hanya lupa password —
// tidak ada lupa username agar endpoint tidak jadi enumeration oracle.
Route::post('/no-auth/forgot-password', [PasswordResetController::class, 'forgot']);
Route::post('/no-auth/reset-password', [PasswordResetController::class, 'reset']);

Route::group([
    'middleware' => ['setguard:api', 'auth.rest']
], function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Users
    Route::get('/users', [UsersController::class, 'index'])
        ->middleware('permission:view-users');
    Route::get('/users/dataset', [UsersController::class, 'dataset'])
        ->middleware('permission:view-users');
    Route::get('/users/student-filter-options', [UsersController::class, 'studentFilterOptions'])
        ->middleware('permission:view-users');
    Route::get('/users/{id}', [UsersController::class, 'show'])
        ->middleware('permission:show-users');
    Route::post('/users/create', [UsersController::class, 'create'])
        ->middleware('permission:create-users');
    Route::put('/users/update', [UsersController::class, 'update'])
        ->middleware('permission:update-users');
    Route::post('/users/update-status', [UsersController::class, 'updateStatus'])
        ->middleware('permission:update-users');
    Route::post('/users/reset-password', [UsersController::class, 'resetPassword'])
        ->middleware('permission:update-users');
    Route::post('/users/change-password', [UsersController::class, 'changePassword']);
    Route::put('/users/update-profile', [UsersController::class, 'updateProfile']);
    Route::delete('/users/delete', [UsersController::class, 'destroy'])
        ->middleware('permission:delete-users');

    // Import siswa (bulk via Excel) + kelulusan per tahun lulus
    Route::get('/students/import-template', [StudentImportController::class, 'template'])
        ->middleware('permission:create-users');
    Route::post('/students/import-upload', [StudentImportController::class, 'upload'])
        ->middleware('permission:create-users');
    Route::post('/students/import-preview', [StudentImportController::class, 'preview'])
        ->middleware('permission:create-users');
    Route::post('/students/import-commit', [StudentImportController::class, 'commit'])
        ->middleware('permission:create-users');
    Route::post('/students/graduate', [StudentImportController::class, 'graduate'])
        ->middleware('permission:update-users');
    Route::post('/students/purge-graduates', [StudentImportController::class, 'purgeGraduates'])
        ->middleware('permission:delete-users');
    Route::get('/students/print-credentials', [StudentImportController::class, 'printCredentials'])
        ->middleware('permission:view-users');


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
    Route::get('/missions/{id}', [MissionController::class, 'show'])
        ->middleware('permission:show-missions');
    Route::post('/missions/create', [MissionController::class, 'create'])
        ->middleware('permission:create-missions');
    Route::put('/missions/update', [MissionController::class, 'update'])
        ->middleware('permission:update-missions');
    Route::delete('/missions/delete', [MissionController::class, 'destroy'])
        ->middleware('permission:delete-missions');

    // Majors
    Route::get('/majors', [MajorsController::class, 'index'])
        ->middleware('permission:view-majors');
    Route::get('/majors/{id}', [MajorsController::class, 'show'])
        ->middleware('permission:show-majors');
    Route::post('/majors/create', [MajorsController::class, 'create'])
        ->middleware('permission:create-majors');
    Route::put('/majors/update', [MajorsController::class, 'update'])
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
    Route::delete('/major-gallery/delete', [MajorGalleryController::class, 'destroy'])
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
    Route::get('/news-categories/dataset', [NewsCategoriesController::class, 'dataset'])
        ->middleware('permission:view-news-categories');
    Route::get('/news-categories/{id}', [NewsCategoriesController::class, 'show'])
        ->middleware('permission:show-news-categories');
    Route::post('/news-categories/create', [NewsCategoriesController::class, 'create'])
        ->middleware('permission:create-news-categories');
    Route::put('/news-categories/update', [NewsCategoriesController::class, 'update'])
        ->middleware('permission:update-news-categories');
    Route::delete('/news-categories/delete', [NewsCategoriesController::class, 'destroy'])
        ->middleware('permission:delete-news-categories');

    // Votings
    Route::get('/votings', [VotingController::class, 'index'])
        ->middleware('permission:view-votings');
    Route::get('/votings/{id}/dpt-preview', [VotingController::class, 'dptPreview'])
        ->middleware('permission:view-votings');
    Route::get('/votings/{id}/result', [VotingController::class, 'result'])
        ->middleware('permission:view-votings');
    Route::get('/votings/{id}/dpt', [VotingController::class, 'dptList'])
        ->middleware('permission:view-votings');
    Route::post('/votings/close', [VotingController::class, 'closeVoting'])
        ->middleware('permission:update-votings');
    Route::get('/votings/{id}', [VotingController::class, 'show'])
        ->middleware('permission:show-votings');
    Route::post('/votings/create', [VotingController::class, 'create'])
        ->middleware('permission:create-votings');
    Route::post('/votings/update-highlight', [VotingController::class, 'updateHighlight'])
        ->middleware('permission:update-votings');
    Route::put('/votings/update', [VotingController::class, 'update'])
        ->middleware('permission:update-votings');
    Route::delete('/votings/delete', [VotingController::class, 'destroy'])
        ->middleware('permission:delete-votings');
    Route::post('/votings/freeze-dpt', [VotingController::class, 'freezeDpt'])
        ->middleware('permission:update-votings');

    // Bilik TPS — check-in oleh panitia (butuh kelola voting)
    Route::post('/booth/checkin', [BoothController::class, 'checkin'])
        ->middleware('permission:update-votings');
    // Bilik remote dari akun sendiri (semua role yang login, tanpa permission khusus)
    Route::post('/booth/remote-login', [BoothController::class, 'remoteLogin']);
    Route::get('/booth/my-status', [BoothController::class, 'mystatus']);

    // Bilik publik (laptop TPS tanpa login akun): login pass + mencoblos.
    // Ditaruh di luar group auth agar bisa diakses tanpa JWT.


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
    Route::get('/feedbacks-categories/dataset', [FeedbacksCategoriesController::class, 'dataset'])
        ->middleware('permission:view-feedbacks-categories');
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
