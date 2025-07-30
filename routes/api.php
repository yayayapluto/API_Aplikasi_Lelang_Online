<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource("countries", \App\Http\Controllers\CountryController::class);
Route::post("countries/uploadBatchData", [\App\Http\Controllers\CountryController::class, "uploadBatchData"]);

Route::apiResource("provinces", \App\Http\Controllers\ProvinceController::class);
Route::post("provinces/uploadBatchData", [\App\Http\Controllers\ProvinceController::class, "uploadBatchData"]);

Route::apiResource("cities", \App\Http\Controllers\CityController::class);
Route::post("cities/uploadBatchData", [\App\Http\Controllers\CityController::class, "uploadBatchData"]);

Route::apiResource("subdistricts", \App\Http\Controllers\SubdistrictController::class);
Route::post("subdistricts/uploadBatchData", [\App\Http\Controllers\SubdistrictController::class, "uploadBatchData"]);

Route::apiResource("villages", \App\Http\Controllers\VillageController::class);
Route::post("villages/uploadBatchData", [\App\Http\Controllers\VillageController::class, "uploadBatchData"]);

Route::apiResource("jobTypes", \App\Http\Controllers\JobTypeController::class);
Route::post("jobTypes/uploadBatchData", [\App\Http\Controllers\JobTypeController::class, "uploadBatchData"]);

Route::apiResource("categories", \App\Http\Controllers\CategoryController::class);
Route::post("categories/uploadBatchData", [\App\Http\Controllers\CategoryController::class, "uploadBatchData"]);

Route::apiResource("itemTypes", \App\Http\Controllers\ItemTypeController::class);
Route::post("itemTypes/uploadBatchData", [\App\Http\Controllers\ItemTypeController::class, "uploadBatchData"]);

Route::apiResource("objectTypes", \App\Http\Controllers\ObjectTypeController::class);
Route::post("objectTypes/uploadBatchData", [\App\Http\Controllers\ObjectTypeController::class, "uploadBatchData"]);

Route::apiResource("files", \App\Http\Controllers\FileController::class);

Route::apiResource("kpknl", \App\Http\Controllers\KpknlController::class);
Route::post("kpknl/uploadBatchData", [\App\Http\Controllers\KpknlController::class, "uploadBatchData"]);
