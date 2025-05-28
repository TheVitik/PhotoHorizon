<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::prefix('profile')->group(function () {
        Route::post('/contacts', [ProfileController::class, 'addContact']);
        Route::delete('/contacts/{id}', [ProfileController::class, 'removeContact']);

        Route::get('/', [ProfileController::class, 'me']);
        Route::post('/', [ProfileController::class, 'update']);
    });

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::get('/{id}/photos', [UserController::class, 'getUserPhotos']);
        Route::get('/{id}/contests', [UserController::class, 'getUserContests']);
        Route::post('/{id}/follow', [UserController::class, 'followUser']);
        Route::post('/{id}/unfollow', [UserController::class, 'unfollowUser']);
        Route::get('/{id}/followers', [UserController::class, 'getFollowers']);
        Route::get('/{id}/following', [UserController::class, 'getFollowing']);
    });

    // Photo routes
    Route::prefix('photos')->group(function () {
        Route::get('/', [PhotoController::class, 'index']);
        Route::get('/in-location', [PhotoController::class, 'getByLocation']);
        Route::post('/', [PhotoController::class, 'store']);
        Route::get('/{id}', [PhotoController::class, 'show']);
        Route::put('/{id}', [PhotoController::class, 'update']);
        Route::delete('/{id}', [PhotoController::class, 'destroy']);
        Route::post('/{id}/like', [PhotoController::class, 'like']);
        Route::delete('/{id}/dislike', [PhotoController::class, 'dislike']);
        Route::post('/{photoId}/contests/{contestId}', [PhotoController::class, 'addToContest']);

        // Comment routes
        Route::prefix('{photoId}/comments')->group(function () {
            Route::post('/', [CommentController::class, 'store']);
            Route::put('/{commentId}', [CommentController::class, 'update']);
            Route::delete('/{commentId}', [CommentController::class, 'destroy']);
        });
    });

    // Contest routes
    Route::prefix('contests')->group(function () {
        Route::get('/', [ContestController::class, 'index']);
        Route::post('/', [ContestController::class, 'store']);
        Route::get('/{id}', [ContestController::class, 'show']);
        Route::put('/{id}', [ContestController::class, 'update']);
        Route::delete('/{id}', [ContestController::class, 'destroy']);

        Route::get('/{id}/participants', [ContestController::class, 'getParticipants']);
        Route::get('/{id}/stats', [ContestController::class, 'getStats']);
        Route::patch('/{id}/finish', [ContestController::class, 'finishContest']);
    });

    // Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{id}/photos', [CategoryController::class, 'getCategoryPhotos']);
    });

    // Location routes
    Route::prefix('locations')->group(function () {
        Route::get('/countries', [LocationController::class, 'getCountries']);
        Route::get('/countries/{id}/regions', [LocationController::class, 'getCountryRegions']);
        Route::get('/regions/{id}/cities', [LocationController::class, 'getRegionCities']);
        Route::get('/cities/{id}/users', [LocationController::class, 'getCityUsers']);
        Route::get('/countries/{id}/users', [LocationController::class, 'getCountryUsers']);
    });
});