<?php

use Illuminate\Support\Facades\Route;
Route::get('auto-crud-test', function () {
    return response()->json(['message' => 'Auto CRUD package is working!']);
});

Route::middleware(['auth'])->group(function () {
    
    Route::resource('{$name}', 'App\Http\Controllers\\' . ucfirst($name) . 'Controller');
});


Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::resource('{$name}', 'App\Http\Controllers\Dashboard\\' . ucfirst($name) . 'Controller');

});

