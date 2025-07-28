<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::apiResource("countries", \App\Http\Controllers\CountryController::class);
Route::apiResource("provinces", \App\Http\Controllers\ProvinceController::class);
