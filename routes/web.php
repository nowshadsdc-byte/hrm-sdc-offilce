<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;


Route::inertia('/', 'welcome')->name('home');

Route::resource('employees', EmployeeController::class);

route::get("/tahrima",function(){
    return "ok";
})->name("tahrima");
