<?php

use App\Models\Cabang;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $cabangs = Cabang::all();
    return view('pages.home', compact('cabangs'));
});
