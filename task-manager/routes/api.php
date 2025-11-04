<?php

use App\Http\Controllers\API\V1\Auth\AuthController;
use App\Http\Controllers\API\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\GroupController;
use App\Http\Controllers\API\V1\GroupUserController;
use App\Http\Controllers\API\V1\TaskAssignController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\Tasks\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Task;

Route::bind('task', function ($value) {
    return Task::withTrashed()->where('id', $value)->firstOrFail();
});

Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPasswordSendOtp']);
        Route::post('forgot-password/verify-otp', [ForgotPasswordController::class, 'forgotPasswordVerifyOtp']);
        Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);

        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::get('user', [UserController::class, 'profile']);
            Route::put('user', [UserController::class, 'update']);
            Route::post('logout', [AuthController::class, 'logout']);

            Route::get('users', [UserController::class, 'index']);
        });
    });

    // Dashboard route
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/admin/dashboard-data', [DashboardController::class, 'adminData'])
            ->middleware('Admin');

        Route::get('/user/dashboard-data', [DashboardController::class, 'userData']);
    });

    // Group resource route
    Route::group(['middleware' => ['auth:sanctum', 'Admin']], function () {
            Route::apiResource('groups', GroupController::class);
        });

    // Group User resource route
    // user access
    Route::middleware('auth:sanctum')->get('groups/{group}/users', [GroupUserController::class, 'list']);

// admin access
    Route::middleware(['auth:sanctum', 'Admin'])->group(function () {
        Route::post('groups/{group}/users', [GroupUserController::class, 'store']);
        Route::delete('groups/{group}/users/{user}', [GroupUserController::class, 'destroy']);
    });

    // Normal user access
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('tasks', [TaskController::class, 'index']);
        Route::get('tasks/{task}', [TaskController::class, 'show']);
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
    });

// Admin-only routes
    Route::middleware(['auth:sanctum', 'Admin'])->group(function () {
        Route::get('trashed-tasks', [TaskController::class, 'trashed']);
        Route::patch('tasks/{task}/restore', [TaskController::class, 'restore']);
        Route::delete('tasks/{task}/force-delete', [TaskController::class, 'forceDelete']);

        Route::post('tasks', [TaskController::class, 'store']);
        Route::put('tasks/{task}', [TaskController::class, 'update']);
        Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    });


    // Normal user
    Route::middleware('auth:sanctum')->group(function (){
        Route::get('tasks/{task}/assignments', [TaskAssignController::class, 'list']);
        Route::get('tasks/{task}/assignments/current', [TaskAssignController::class, 'current']);

        Route::patch('tasks/{taskId}/assignments/{assignmentId}/update-task-status', [TaskAssignController::class, 'updateTaskStatus']);
    });

// Admin-only
    Route::middleware(['auth:sanctum', 'Admin'])->group(function (){
        Route::post('tasks/{task}/assignments', [TaskAssignController::class, 'store']);
        Route::delete('tasks/{task}/assignments/{assignment}', [TaskAssignController::class, 'destroy']);
    });

});
