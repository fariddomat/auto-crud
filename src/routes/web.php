<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\\{$name}Controller;

use App\Http\Controllers\Dashboard\\{$name}Controller;
Route::get('auto-crud-test', function () {
    return response()->json(['message' => 'Auto CRUD package is working!']);
});

Route::middleware(['auth'])->group(function () {
    Route::resource('{$name}', {$name}Controller::class);
});


Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::resource('{$name}', {$name}Controller::class);
});

