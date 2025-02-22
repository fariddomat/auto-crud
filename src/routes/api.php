<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{Model}Controller;

Route::apiResource('{model}', ModelController::class);
