<?php

use Illuminate\Support\Facades\Route;

Route::get('auto-crud-test', function () {
    return response()->json(['message' => 'Auto CRUD package is working!']);
});

