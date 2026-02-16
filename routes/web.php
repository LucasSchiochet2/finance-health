<?php

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use App\Models\Bill;
use App\Models\CategoryBill;
use App\Models\User;
Route::get('/', function () {
    return view('welcome');
});
