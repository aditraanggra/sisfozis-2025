<?php

use App\Http\Controllers\API\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Versioned API routes are grouped by prefix (e.g. /api/v1) to allow future
| versions to coexist. Shared middleware is applied per-version here.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:10,1')->post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        require base_path('routes/v1/routes.php');
    });
});
