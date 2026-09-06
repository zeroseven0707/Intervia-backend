<?php

use App\Models\Position;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing', [
        'positions' => Position::active()->count(),
    ]);
});
