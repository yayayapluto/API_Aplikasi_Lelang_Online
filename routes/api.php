<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource("countries", \App\Http\Controllers\CountryController::class);
Route::apiResource("provinces", \App\Http\Controllers\ProvinceController::class);
Route::apiResource("cities", \App\Http\Controllers\CityController::class);
Route::apiResource("subdistricts", \App\Http\Controllers\SubdistrictController::class);
Route::apiResource("villages", \App\Http\Controllers\VillageController::class);

Route::apiResource("jobTypes", \App\Http\Controllers\JobTypeController::class);

Route::apiResource("categories", \App\Http\Controllers\CategoryController::class);

Route::apiResource("itemTypes", \App\Http\Controllers\ItemTypeController::class);
