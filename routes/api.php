<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode']);
Route::post('/verify-reset-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Videos
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/videos/{video}', [VideoController::class, 'show']);
    Route::get('/videos/{video}/stream', [VideoController::class, 'streamVideo']);

    // Comments
    Route::post('/videos/{video}/comments', [CommentController::class, 'store']);
    Route::put('/videos/{video}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/videos/{video}/comments/{comment}', [CommentController::class, 'destroy']);

    // Likes
    Route::post('/videos/{video}/toggle-like', [LikeController::class, 'toggle']);
    Route::get('/videos/{video}/like-status', [LikeController::class, 'status']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // Subject and Lesson routes
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
    Route::get('/lessons/{lesson}', [LessonController::class, 'show']);
    Route::get('/lessons/{lesson}/stream', [LessonController::class, 'stream']);
    
    // Podcast Routes - Moving them inside the authenticated group
    Route::get('podcasts', [PodcastController::class, 'index']);
    Route::get('podcasts/{podcast}', [PodcastController::class, 'show']);
    Route::get('podcasts/{podcast}/stream', [PodcastController::class, 'stream']);
    Route::post('podcasts/{podcast}/play', [PodcastController::class, 'incrementPlays']);
});
