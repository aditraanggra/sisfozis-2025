<?php

use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\SuratKeluarController;
use App\Http\Controllers\API\V1\SuratMasukController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class);
Route::apiResource('surat-masuk', SuratMasukController::class);
Route::apiResource('surat-keluar', SuratKeluarController::class);
